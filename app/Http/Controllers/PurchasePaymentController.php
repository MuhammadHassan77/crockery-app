<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\PurchasePaymentService;
use Illuminate\Http\Request;

class PurchasePaymentController extends Controller
{
    public function __construct(private PurchasePaymentService $purchasePaymentService) {}

    public function store(Request $request, PurchaseInvoice $purchase_invoice)
    {
        try {
            $request->validate([
                'amount'         => ['required', 'numeric', 'min:0.01', 'max:' . $purchase_invoice->remaining_amount],
                'payment_method' => ['required', 'in:cash,bank,bank_transfer,easypaisa,jazzcash,cheque,other'],
                'payment_date'   => ['nullable', 'date'],
                'notes'          => ['nullable', 'string'],
            ]);

            $payment = $this->purchasePaymentService->record($purchase_invoice, [
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'paid_at'        => $request->payment_date ?? now(),
                'notes'          => $request->notes,
            ]);

            $purchase_invoice->refresh();

            return response()->json([
                'payment' => [
                    'id'                   => $payment->id,
                    'payment_date'         => $payment->payment_date->format('d M Y'),
                    'payment_method_label' => $payment->payment_method_label,
                    'amount_formatted'     => number_format($payment->amount, 0),
                    'notes'                => $payment->notes,
                    'delete_url'           => route('payments.destroy', $payment),
                ],
                'invoice' => [
                    'status'            => $purchase_invoice->status,
                    'status_label'      => ucfirst($purchase_invoice->status),
                    'paid_formatted'    => number_format($purchase_invoice->paid_amount, 0),
                    'remaining'         => $purchase_invoice->remaining_amount,
                    'remaining_formatted' => number_format($purchase_invoice->remaining_amount, 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to record payment. Please try again.', null, 422);
        }
    }

    public function destroy(PurchasePayment $purchase_payment)
    {
        try {
            $purchase_invoice = $purchase_payment->invoice;
            $purchase_payment->delete();
            $this->purchasePaymentService->syncPurchaseInvoiceStatus($purchase_invoice);
            $purchase_invoice->refresh();

            return response()->json([
                'invoice' => [
                    'status'              => $purchase_invoice->status,
                    'status_label'        => ucfirst($purchase_invoice->status),
                    'paid_formatted'      => number_format($purchase_invoice->paid_amount, 0),
                    'remaining'           => $purchase_invoice->remaining_amount,
                    'remaining_formatted' => number_format($purchase_invoice->remaining_amount, 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete payment. Please try again.', null, 422);
        }
    }
}
