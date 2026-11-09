<?php

namespace App\Console\Commands;

use App\Enums\PaymentHistoryType;
use App\Models\Customer;
use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use App\Models\PaymentHistory;
use App\Services\CustomerAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeCustomerAccounts extends Command
{
    protected $signature = 'customers:initialize-accounts {--dry-run}';

    protected $description = 'Normalize customer invoice credits and initialize receipt allocations and balances';

    public function handle(CustomerAccountService $accountService): int
    {
        $negativeInvoices = Invoice::query()
            ->whereNotNull('customer_id')
            ->where('total_amount', '<', 0);

        if ($this->option('dry-run')) {
            $this->info('Customer accounts to initialize: ' . Customer::count());
            $this->info('Negative customer invoices to normalize: ' . $negativeInvoices->count());

            return self::SUCCESS;
        }

        Customer::query()->with('invoices.payments')->chunkById(100, function ($customers) use ($accountService) {
            foreach ($customers as $customer) {
                DB::transaction(function () use ($customer, $accountService) {
                    $legacyReceipts = $customer->paymentHistories()
                        ->where('type', PaymentHistoryType::Legacy->value)
                        ->get();
                    $linkedAmounts = [];

                    PaymentHistory::query()
                        ->where('model_id', $customer->id)
                        ->where('type', PaymentHistoryType::Credit->value)
                        ->update(['payment_method' => null]);

                    foreach ($customer->invoices as $invoice) {
                        $payments = $invoice->payments;

                        foreach ($payments as $payment) {
                            if (CustomerPaymentAllocation::where('payment_id', $payment->id)->exists()) {
                                continue;
                            }

                            $receipt = $legacyReceipts->first(function ($history) use ($payment, &$linkedAmounts) {
                                $sameMethod = $history->payment_method === $payment->payment_method;
                                $sameReceiver = (int) $history->received_by === (int) $payment->received_by;
                                $nearSameTime = $history->created_at && $payment->created_at
                                    && abs($history->created_at->diffInSeconds($payment->created_at)) <= 60;
                                $remainingReceipt = (float) $history->amount - ($linkedAmounts[$history->id] ?? 0);

                                return $sameMethod && $sameReceiver && $nearSameTime
                                    && $remainingReceipt + 0.009 >= (float) $payment->amount;
                            });

                            if ($receipt) {
                                $receipt->update(['type' => 'receipt']);
                                $linkedAmounts[$receipt->id] = ($linkedAmounts[$receipt->id] ?? 0) + (float) $payment->amount;
                            } else {
                                $receipt = PaymentHistory::create([
                                    'model_id' => $customer->id,
                                    'model_name' => $customer->getMorphClass(),
                                    'amount' => $payment->amount,
                                    'payment_method' => $payment->payment_method,
                                    'type' => 'receipt',
                                    'target_invoice_id' => $invoice->id,
                                    'received_by' => $payment->received_by ?? $invoice->created_by,
                                    'payment_date' => $payment->paid_at?->toDateString() ?? $invoice->invoice_date,
                                    'notes' => $payment->notes,
                                ]);
                            }

                            CustomerPaymentAllocation::create([
                                'payment_history_id' => $receipt->id,
                                'payment_id' => $payment->id,
                                'amount' => $payment->amount,
                            ]);
                        }

                        if ((float) $invoice->total_amount >= 0) {
                            continue;
                        }

                        $credit = PaymentHistory::withTrashed()
                            ->where('type', PaymentHistoryType::Credit->value)
                            ->where('source_invoice_id', $invoice->id)
                            ->first();

                        $creditData = [
                            'model_id' => $customer->id,
                            'model_name' => $customer->getMorphClass(),
                            'amount' => abs((float) $invoice->total_amount),
                            'payment_method' => null,
                            'type' => PaymentHistoryType::Credit->value,
                            'source_invoice_id' => $invoice->id,
                            'received_by' => $invoice->created_by,
                            'payment_date' => $invoice->invoice_date,
                            'notes' => 'Credit migrated from a negative invoice total',
                        ];

                        if ($credit) {
                            $credit->restore();
                            $credit->update($creditData);
                        } else {
                            PaymentHistory::create($creditData);
                        }

                        $invoice->forceFill([
                            'total_amount' => 0,
                            'paid_amount' => 0,
                            'status' => 'paid',
                        ])->save();
                    }

                    $accountService->reconcile($customer);
                });
            }
        });

        $this->info('Customer account balances and receipt allocations initialized.');

        return self::SUCCESS;
    }
}