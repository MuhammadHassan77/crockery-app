<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use Illuminate\Support\Facades\DB;

class PurchasePaymentService
{
    public function record(PurchaseInvoice $invoice, array $data): PurchasePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $data['received_by'] = auth()->id();
            $data['paid_at']     = $data['paid_at'] ?? now();

            $payment = $invoice->purchase_payments()->create($data);
            $this->syncPurchaseInvoiceStatus($invoice);

            return $payment;
        });
    }

    public function syncPurchaseInvoiceStatus(PurchaseInvoice $invoice): void
    {
        $paid = $invoice->purchase_payments()->sum('amount');

        $status = match (true) {
            $paid <= 0                          => 'pending',
            $paid >= $invoice->total_amount     => 'paid',
            default                             => 'partial',
        };

        $invoice->update([
            'paid_amount' => $paid,
            'status'      => $status,
        ]);
    }
}
