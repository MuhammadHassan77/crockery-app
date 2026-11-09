<?php

namespace App\Services;

use App\Enums\PaymentHistoryType;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\CustomerPaymentAllocation;
use App\Models\PaymentHistory;
use App\Models\ProductSize;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private StockService $stockService,
        private PaymentService $paymentService,
        private CustomerAccountService $customerAccountService,
    ) {}

    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['invoice_number'] = $this->generateNumber();
            $data['share_token']    = Str::uuid();
            $data['created_by']     = auth()->id();
            $data['invoice_date']   = $data['invoice_date'] ?? now()->toDateString();

            $subtotal = collect($items)->sum(fn($i) => isset($i['weight']) && $i['weight'] > 0
                ? $i['weight'] * $i['unit_price']
                : $i['quantity'] * $i['unit_price']);
            $data['subtotal'] = $subtotal;

            $data['nug_amount'] = isset($data['nug_amount']) ? (float) $data['nug_amount'] : 0;

            $grossAmount = max(0, $subtotal
                - (float) ($data['discount_amount'] ?? 0)
                + (float) ($data['tax_amount'] ?? 0)
                + (float) ($data['nug_amount'] ?? 0));
            $adjustmentAmount = (float) ($data['return_item_amount'] ?? 0)
                + (float) ($data['exchange_item_amount'] ?? 0);
            $data['total_amount'] = max(0, $grossAmount - $adjustmentAmount);
            $excessAdjustment = max(0, $adjustmentAmount - $grossAmount);
            $data['paid_amount']   = 0;
            $data['initial_payment'] = max(0, (float) ($data['initial_payment'] ?? 0));
            $cashRefundAmount = max(0, $data['initial_payment'] + $adjustmentAmount - $grossAmount);
            $data['status']        = $data['total_amount'] > 0 ? 'pending' : 'paid';

            $customer = !empty($data['customer_id'])
                ? Customer::query()->whereKey($data['customer_id'])->lockForUpdate()->first()
                : null;
            $data['previous_balance'] = $customer ? (float) $customer->account_balance : null;
            $data['cash_refund_amount'] = $customer ? 0 : $cashRefundAmount;

            $invoice = Invoice::create(
                collect($data)->except(['items', 'payment_method'])->toArray()
            );

            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_size_id' => $item['product_size_id'],
                    'product_name'    => $item['product_name'],
                    'product_name_ur' => $item['product_name_ur'] ?? null,
                    'size_label'      => $item['size_label'],
                    'size_label_ur'   => $item['size_label_ur'] ?? $item['size_label'],
                    'quantity'        => $item['quantity'],
                    'weight'          => $item['weight'] ?? null,
                    'unit_price'      => $item['unit_price'],
                    'total_price'     => isset($item['weight']) && $item['weight'] > 0
                        ? $item['weight'] * $item['unit_price']
                        : $item['quantity'] * $item['unit_price'],
                ]);

                $this->stockService->deduct($item['product_size_id'], $item['quantity'], $invoice);
            }

            if ($customer) {
                if ((float) ($data['initial_payment'] ?? 0) > 0) {

                    $this->customerAccountService->createReceipt($customer, [
                        'amount' => $data['initial_payment'],
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'source_invoice_id' => $invoice->id,
                        'notes' => 'Payment received when invoice was created',
                    ], $invoice);
                }

                if ($excessAdjustment > 0) {
                    $this->customerAccountService->createReceipt($customer, [
                        'amount' => $excessAdjustment,
                        'source_invoice_id' => $invoice->id,
                        'notes' => 'Credit from excess return or exchange amount',
                    ], null, PaymentHistoryType::Credit->value);
                }

                $this->customerAccountService->reconcile($customer);
            } else {
                $paymentAmount = min((float) $data['initial_payment'], (float) $invoice->total_amount);
                if ($paymentAmount > 0) {
                    $this->paymentService->record($invoice, [
                        'amount' => $paymentAmount,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                    ]);
                }
                $this->paymentService->syncInvoiceStatus($invoice);
            }

            return $invoice->fresh(['items', 'payments']);
        });
    }

    /**
     * Update an existing invoice with new metadata and items.
     * Restores previous stock, recreates items, deducts new stock, optionally updates prices.
     */
    public function updateInvoice(Invoice $invoice, array $data, array $items, bool $updatePrices = false): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items, $updatePrices) {
            $oldCustomerId = $invoice->customer_id;
            // Restore stock from existing items
            foreach ($invoice->items as $old) {
                $this->stockService->restoreSale($old->product_size_id, $old->quantity, $invoice);
            }

            // Remove old items
            $invoice->items()->delete();

            // Recompute totals
            $subtotal = collect($items)->sum(fn($i) => isset($i['weight']) && $i['weight'] > 0 ? $i['weight'] * $i['unit_price'] : $i['quantity'] * $i['unit_price']);
            $data['subtotal'] = $subtotal;

            $data['nug_amount'] = isset($data['nug_amount']) ? (float) $data['nug_amount'] : 0;

            $grossAmount = max(0, $subtotal
                - (float) ($data['discount_amount'] ?? 0)
                + (float) ($data['tax_amount'] ?? 0)
                + (float) ($data['nug_amount'] ?? 0));
            $adjustmentAmount = (float) ($data['return_item_amount'] ?? 0)
                + (float) ($data['exchange_item_amount'] ?? 0);
            $data['total_amount'] = max(0, $grossAmount - $adjustmentAmount);
            $excessAdjustment = max(0, $adjustmentAmount - $grossAmount);

            $customer = !empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;
            $initialPayment = array_key_exists('initial_payment', $data)
                ? max(0, (float) ($data['initial_payment'] ?? 0))
                : max(0, (float) $invoice->initial_payment);
            $data['initial_payment'] = $initialPayment;
            $cashRefundAmount = max(0, $initialPayment + $adjustmentAmount - $grossAmount);
            $data['cash_refund_amount'] = $customer ? 0 : $cashRefundAmount;
            /*
            if ($excessAdjustment > 0 && ! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Select a regular customer to retain excess return or exchange value as account credit.',
                ]);
            }
            */
            if ($oldCustomerId && ! $customer && PaymentHistory::query()
                ->where('model_id', $oldCustomerId)
                ->where('type', 'receipt')
                ->where('target_invoice_id', $invoice->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'customer_id' => 'An invoice with customer-account receipts cannot be changed to a walk-in invoice.',
                ]);
            }

            $invoice->update(collect($data)->except(['items', 'payment_method', 'previous_balance'])->toArray());

            if ($oldCustomerId && $customer && $oldCustomerId !== $customer->id) {
                PaymentHistory::query()
                    ->where('model_id', $oldCustomerId)
                    ->where('model_name', $customer->getMorphClass())
                    ->where(function ($query) use ($invoice) {
                        $query->where('source_invoice_id', $invoice->id)
                            ->orWhere('target_invoice_id', $invoice->id);
                    })
                    ->update(['model_id' => $customer->id]);
            }

            // Create new items and adjust stock
            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_size_id' => $item['product_size_id'],
                    'product_name'    => $item['product_name'],
                    'product_name_ur' => $item['product_name_ur'] ?? null,
                    'size_label'      => $item['size_label'],
                    'size_label_ur'   => $item['size_label_ur'] ?? $item['size_label'],
                    'quantity'        => $item['quantity'],
                    'weight'          => $item['weight'] ?? null,
                    'unit_price'      => $item['unit_price'],
                    'total_price'     => isset($item['weight']) && $item['weight'] > 0 ? $item['weight'] * $item['unit_price'] : $item['quantity'] * $item['unit_price'],
                ]);

                $this->stockService->deduct($item['product_size_id'], $item['quantity'], $invoice);

                if ($updatePrices) {
                    $size = ProductSize::find($item['product_size_id']);
                    if ($size) {
                        app(\App\Services\PriceService::class)->setPrice($size, $item['unit_price']);
                    }
                }
            }

            if ($customer) {
                $this->syncInvoiceReceipts($invoice, $customer, $data, $excessAdjustment);
            } else {
                $this->syncWalkInPayments($invoice, $initialPayment, $data['payment_method'] ?? null);
            }

            if ($oldCustomerId && $oldCustomerId !== $invoice->customer_id) {
                $oldCustomer = Customer::find($oldCustomerId);
                if ($oldCustomer) {
                    $this->customerAccountService->reconcile($oldCustomer);
                }
            }

            if ($customer) {
                $this->customerAccountService->reconcile($customer);
            }

            return $invoice->fresh(['items', 'payments']);
        });
    }

    private function syncInvoiceReceipts(Invoice $invoice, Customer $customer, array $data, float $excessAdjustment): void
    {
        $credit = PaymentHistory::withTrashed()
            ->where('type', 'credit')
            ->where('source_invoice_id', $invoice->id)
            ->first();

        if ($excessAdjustment > 0) {
            $creditData = [
                'model_id' => $customer->id,
                'model_name' => $customer->getMorphClass(),
                'amount' => $excessAdjustment,
                'payment_method' => null,
                'type' => PaymentHistoryType::Credit->value,
                'source_invoice_id' => $invoice->id,
                'received_by' => auth()->id() ?? $invoice->created_by,
                'payment_date' => $invoice->invoice_date,
                'notes' => 'Credit from excess return or exchange amount',
            ];

            if ($credit) {
                $credit->restore();
                $credit->update($creditData);
            } else {
                PaymentHistory::create($creditData);
            }
        } elseif ($credit) {
            $this->customerAccountService->discardReceiptAllocations($credit);
            $credit->delete();
        }

        if (! array_key_exists('initial_payment', $data)) {
            $this->paymentService->syncInvoiceStatus($invoice);

            return;
        }

        $desiredAmount = max(0, (float) $data['initial_payment']);
        $currentPaid = (float) $invoice->payments()->sum('amount');
        $paymentIds = $invoice->payments()->pluck('id');
        $mappedPayment = CustomerPaymentAllocation::query()->whereIn('payment_id', $paymentIds)->exists();
        $targetReceipts = PaymentHistory::withTrashed()
            ->where('type', 'receipt')
            ->where('target_invoice_id', $invoice->id)
            ->orderBy('id')
            ->get();
        $activeTargetReceipts = $targetReceipts->filter(fn($receipt) => ! $receipt->trashed())->values();

        if ($activeTargetReceipts->isNotEmpty()) {
            $amountRemaining = $desiredAmount;
            foreach ($activeTargetReceipts as $receipt) {
                $keep = min((float) $receipt->amount, $amountRemaining);
                $amountRemaining -= $keep;

                if ($keep <= 0) {
                    $this->customerAccountService->discardReceiptAllocations($receipt);
                    $receipt->delete();
                } else {
                    $receipt->update(['amount' => $keep]);
                }
            }

            if ($amountRemaining > 0) {
                $activeTargetReceipts->first()->increment('amount', $amountRemaining);
            }
        } elseif ($mappedPayment) {
            if (abs($desiredAmount - $currentPaid) > 0.009) {
                throw ValidationException::withMessages([
                    'initial_payment' => 'This invoice payment came from a customer receipt. Edit or reverse that receipt from the customer account instead.',
                ]);
            }
        } elseif ($invoice->payments()->exists()) {
            $invoice->payments()->delete();
            if ($desiredAmount > 0) {
                PaymentHistory::create([
                    'model_id' => $customer->id,
                    'model_name' => $customer->getMorphClass(),
                    'amount' => $desiredAmount,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'type' => 'receipt',
                    'target_invoice_id' => $invoice->id,
                    'source_invoice_id' => $invoice->id,
                    'received_by' => auth()->id() ?? $invoice->created_by,
                    'payment_date' => $invoice->invoice_date,
                    'notes' => 'Payment updated while editing invoice',
                ]);
            }
        } elseif ($desiredAmount > 0) {
            PaymentHistory::create([
                'model_id' => $customer->id,
                'model_name' => $customer->getMorphClass(),
                'amount' => $desiredAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'type' => 'receipt',
                'target_invoice_id' => $invoice->id,
                'source_invoice_id' => $invoice->id,
                'received_by' => auth()->id() ?? $invoice->created_by,
                'payment_date' => $invoice->invoice_date,
                'notes' => 'Payment entered while editing invoice',
            ]);
        }

        $this->paymentService->syncInvoiceStatus($invoice);
    }

    private function syncWalkInPayments(Invoice $invoice, float $receivedAmount, ?string $paymentMethod): void
    {
        $amountToApply = min($receivedAmount, (float) $invoice->total_amount);
        $payments = $invoice->payments()->orderBy('paid_at')->orderBy('id')->get();
        $remainingAmount = $amountToApply;

        foreach ($payments as $payment) {
            if ($remainingAmount <= 0) {
                $payment->delete();
                continue;
            }

            $keptAmount = min((float) $payment->amount, $remainingAmount);
            if (abs((float) $payment->amount - $keptAmount) > 0.009) {
                $payment->update(['amount' => $keptAmount]);
            }
            $remainingAmount -= $keptAmount;
        }

        if ($remainingAmount > 0) {
            $this->paymentService->record($invoice, [
                'amount' => $remainingAmount,
                'payment_method' => $paymentMethod ?? $payments->last()?->payment_method ?? 'cash',
                'paid_at' => now(),
                'notes' => 'Payment adjusted while editing invoice',
            ]);
        }

        $this->paymentService->syncInvoiceStatus($invoice);
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $last = Invoice::whereYear('created_at', $year)->max('id') ?? 0;

        return 'INV-' . $year . '-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }
}
