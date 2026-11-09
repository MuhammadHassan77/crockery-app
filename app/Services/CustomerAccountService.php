<?php

namespace App\Services;

use App\Enums\PaymentHistoryType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public function __construct(private PaymentService $paymentService) {}

    public function receive(Customer $customer, array $data, ?Invoice $targetInvoice = null, string $type = PaymentHistoryType::Receipt->value): PaymentHistory
    {
        return DB::transaction(function () use ($customer, $data, $targetInvoice, $type) {
            if ($targetInvoice && (int) $targetInvoice->customer_id !== (int) $customer->id) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The target invoice must belong to this customer.',
                ]);
            }

            $receipt = $this->createReceipt($customer, $data, $targetInvoice, $type);

            $this->reconcile($customer);

            return $receipt->fresh(['allocations.payment.invoice']);
        });
    }

    public function createReceipt(Customer $customer, array $data, ?Invoice $targetInvoice = null, string $type = PaymentHistoryType::Receipt->value): PaymentHistory
    {
        $paymentMethod = $type === PaymentHistoryType::Credit->value
            ? null
            : $data['payment_method'] ?? PaymentMethod::Cash->value;

        return PaymentHistory::create([
            'model_id' => $customer->id,
            'model_name' => $customer->getMorphClass(),
            'amount' => $data['amount'],
            'payment_method' => $paymentMethod,
            'type' => $type,
            'target_invoice_id' => $targetInvoice?->id,
            'source_invoice_id' => $data['source_invoice_id'] ?? null,
            'received_by' => $data['received_by'] ?? auth()->id(),
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function reconcile(Customer $customer): Collection
    {
        return DB::transaction(function () use ($customer) {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $receipts = $customer->accountReceipts()->orderBy('id')->get();
            $receiptIds = $receipts->modelKeys();
            $allocations = CustomerPaymentAllocation::query()
                ->whereIn('payment_history_id', $receiptIds)
                ->with('payment.invoice')
                ->get();
            $excludedInvoiceIdsByReceipt = DB::table('customer_payment_allocation_exclusions')
                ->whereIn('payment_history_id', $receiptIds)
                ->get(['payment_history_id', 'invoice_id'])
                ->groupBy('payment_history_id')
                ->map(fn ($exclusions) => $exclusions->pluck('invoice_id')->map(fn ($id) => (int) $id)->all());

            $affectedInvoiceIds = $allocations
                ->pluck('payment.invoice.id')
                ->filter()
                ->unique()
                ->values();

            if ($allocations->isNotEmpty()) {
                CustomerPaymentAllocation::query()->whereIn('payment_history_id', $receiptIds)->delete();
                Payment::query()->whereIn('id', $allocations->pluck('payment_id')->filter()->unique())->delete();
            }

            foreach ($affectedInvoiceIds as $invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    $this->paymentService->syncInvoiceStatus($invoice);
                }
            }

            $invoices = $customer->invoices()
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $newAllocations = collect();

            foreach ($receipts as $receipt) {
                $remainingReceipt = (float) $receipt->amount;
                $priorityId = $receipt->target_invoice_id;
                $priorityInvoice = $invoices->firstWhere('id', $priorityId);
                $excludedInvoiceIds = $excludedInvoiceIdsByReceipt->get($receipt->id, []);

                if ($priorityInvoice && ! in_array((int) $priorityInvoice->id, $excludedInvoiceIds, true) && $remainingReceipt > 0) {
                    $remainingReceipt = $this->allocateToInvoice($receipt, $priorityInvoice, $remainingReceipt, $newAllocations);
                }

                foreach ($invoices as $invoice) {
                    if ($remainingReceipt <= 0) {
                        break;
                    }
                    if ($priorityInvoice && $invoice->is($priorityInvoice)) {
                        continue;
                    }
                    if (in_array((int) $invoice->id, $excludedInvoiceIds, true)) {
                        continue;
                    }

                    $remainingReceipt = $this->allocateToInvoice($receipt, $invoice, $remainingReceipt, $newAllocations);
                }
            }

            $invoiceNet = (float) $customer->invoices()->sum(DB::raw('total_amount - paid_amount'));
            $allocatedTotal = (float) CustomerPaymentAllocation::query()
                ->whereIn('payment_history_id', $receiptIds)
                ->sum('amount');
            $receiptTotal = (float) $receipts->sum('amount');
            $unallocatedCredit = max(0, $receiptTotal - $allocatedTotal);

            $customer->forceFill([
                'account_balance' => round($invoiceNet - $unallocatedCredit, 2),
            ])->save();

            return $newAllocations;
        });
    }

    public function voidPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->with('invoice')->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $invoice = $payment->invoice;
            $allocation = CustomerPaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->with('receipt')
                ->first();

            if (! $allocation) {
                $payment->delete();
                $this->paymentService->syncInvoiceStatus($invoice);
                if ($invoice->customer) {
                    $this->reconcile($invoice->customer);
                }

                return ['invoice_ids' => [$invoice->id]];
            }

            $receipt = $allocation->receipt;
            $customer = $receipt->model;
            $invoiceIds = $receipt->allocations()
                ->with('payment')
                ->get()
                ->pluck('payment.invoice_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($customer instanceof Customer) {
                $remainingReceiptAmount = max(0, round((float) $receipt->amount - (float) $allocation->amount, 2));

                if ($remainingReceiptAmount > 0) {
                    DB::table('customer_payment_allocation_exclusions')->insertOrIgnore([
                        'payment_history_id' => $receipt->id,
                        'invoice_id' => $invoice->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $receipt->update(['amount' => $remainingReceiptAmount]);
                } else {
                    $this->discardReceiptAllocations($receipt);
                    $receipt->delete();
                }

                $this->reconcile($customer);
            } else {
                $payment->delete();
                $this->paymentService->syncInvoiceStatus($invoice);
            }

            return ['invoice_ids' => array_values(array_unique([...$invoiceIds, $invoice->id]))];
        });
    }

    public function discardReceiptAllocations(PaymentHistory $receipt): void
    {
        $allocations = $receipt->allocations()->with('payment.invoice')->get();
        $invoiceIds = $allocations->pluck('payment.invoice_id')->filter()->unique();

        if ($allocations->isNotEmpty()) {
            CustomerPaymentAllocation::query()->whereKey($allocations->modelKeys())->delete();
            Payment::query()->whereIn('id', $allocations->pluck('payment_id')->filter()->unique())->delete();
        }

        foreach ($invoiceIds as $invoiceId) {
            if ($invoice = Invoice::find($invoiceId)) {
                $this->paymentService->syncInvoiceStatus($invoice);
            }
        }
    }

    private function allocateToInvoice(PaymentHistory $receipt, Invoice $invoice, float $available, Collection $allocations): float
    {
        $due = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
        if ($due <= 0 || $available <= 0) {
            return $available;
        }

        $amount = min($due, $available);
        $payment = $this->paymentService->record($invoice, [
            'amount' => $amount,
            'payment_method' => match ($receipt->payment_method) {
                'cash', 'bank', 'jazzcash', 'easypaisa', 'cheque', 'other' => $receipt->payment_method,
                'bank_transfer' => 'bank',
                default => 'other',
            },
            'paid_at' => $receipt->payment_date ?? now(),
            'received_by' => $receipt->received_by,
            'notes' => $receipt->notes,
        ]);

        CustomerPaymentAllocation::create([
            'payment_history_id' => $receipt->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
        ]);

        $allocations->push([
            'receipt_id' => $receipt->id,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $amount,
        ]);

        return $available - $amount;
    }
}