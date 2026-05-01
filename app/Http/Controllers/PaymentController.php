<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->remaining_amount],
            'payment_method' => ['required', 'in:cash,bank,bank_transfer,easypaisa,jazzcash,cheque,other'],
            'payment_date'   => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],
        ]);

        $payment = $this->paymentService->record($invoice, [
            'amount'         => $request->amount,
            'payment_method' => $request->payment_method,
            'paid_at'        => $request->payment_date ?? now(),
            'notes'          => $request->notes,
        ]);

        $invoice->refresh();

        $methodLabels = [
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'easypaisa'     => 'EasyPaisa',
            'jazzcash'      => 'JazzCash',
            'cheque'        => 'Cheque',
            'other'         => 'Other',
        ];

        return response()->json([
            'payment' => [
                'id'                   => $payment->id,
                'payment_date'         => $payment->payment_date->format('d M Y'),
                'payment_method_label' => $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method),
                'amount_formatted'     => number_format($payment->amount, 0),
                'notes'                => $payment->notes,
                'delete_url'           => route('payments.destroy', $payment),
            ],
            'invoice' => [
                'status'            => $invoice->status,
                'status_label'      => ucfirst($invoice->status),
                'paid_formatted'    => number_format($invoice->paid_amount, 0),
                'remaining'         => $invoice->remaining_amount,
                'remaining_formatted' => number_format($invoice->remaining_amount, 0),
            ],
        ]);
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;
        $payment->delete();
        $this->paymentService->syncInvoiceStatus($invoice);
        $invoice->refresh();

        return response()->json([
            'invoice' => [
                'status'              => $invoice->status,
                'status_label'        => ucfirst($invoice->status),
                'paid_formatted'      => number_format($invoice->paid_amount, 0),
                'remaining'           => $invoice->remaining_amount,
                'remaining_formatted' => number_format($invoice->remaining_amount, 0),
            ],
        ]);
    }
}
