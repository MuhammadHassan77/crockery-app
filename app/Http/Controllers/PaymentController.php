<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private CustomerAccountService $customerAccountService,
    ) {}

    public function store(Request $request, Invoice $invoice)
    {
        try {
            $request->validate([
                'amount'         => ['required', 'numeric', 'min:0.01'],
                'payment_method' => ['required', 'in:cash,bank,bank_transfer,easypaisa,jazzcash,cheque,other'],
                'payment_date'   => ['nullable', 'date'],
                'notes'          => ['nullable', 'string'],
            ]);

            if (! $invoice->customer && (float) $request->amount > $invoice->remaining_amount) {
                return response()->json(['message' => 'Payment cannot exceed the walk-in invoice balance.'], 422);
            }

            $paymentData = [
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method === 'bank_transfer' ? 'bank' : $request->payment_method,
                'paid_at'        => $request->payment_date ?? now(),
                'notes'          => $request->notes,
            ];
            $receivedAmount = (float) $request->amount;

            if ($invoice->customer) {
                $receipt = $this->customerAccountService->receive(
                    $invoice->customer,
                    array_merge($paymentData, [
                        'payment_date' => $request->payment_date ?? now()->toDateString(),
                    ]),
                    $invoice,
                );
                $receivedAmount = (float) $receipt->amount;
                $payment = $receipt->allocations->first()?->payment;
            } else {
                $payment = $this->paymentService->record($invoice, $paymentData);
            }

            $invoice->refresh();

            if (! $payment) {
                return response()->json(['message' => 'Payment could not be allocated to this invoice.'], 422);
            }

        return response()->json([
            'payment' => [
                'id'                   => $payment->id,
                'payment_date'         => $payment->payment_date->format('d M Y'),
                'payment_method_label' => $payment->payment_method_label,
                'received_amount_formatted' => number_format($receivedAmount, 0),
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
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to record payment. Please try again.', null, 422);
        }
    }

    public function destroy(Payment $payment)
    {
        try {
            $invoice = $payment->invoice;
            $result = $this->customerAccountService->voidPayment($payment);
            $invoice->refresh();

            return response()->json([
                'invoice' => [
                    'status'              => $invoice->status,
                    'status_label'        => ucfirst($invoice->status),
                    'paid_formatted'      => number_format($invoice->paid_amount, 0),
                    'remaining'           => $invoice->remaining_amount,
                    'remaining_formatted' => number_format($invoice->remaining_amount, 0),
                ],
                'affected_invoice_ids' => $result['invoice_ids'],
            ]);
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete payment. Please try again.', null, 422);
        }
    }
}
