<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function record(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $data['received_by'] = $data['received_by'] ?? auth()->id();
            $data['paid_at']     = $data['paid_at'] ?? now();

            $payment = $invoice->payments()->create($data);
            $this->syncInvoiceStatus($invoice);

            return $payment;
        });
    }

    public function syncInvoiceStatus(Invoice $invoice): void
    {
        $paid = $invoice->payments()->sum('amount');

        $status = match (true) {
            $invoice->total_amount <= 0         => 'paid',
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
