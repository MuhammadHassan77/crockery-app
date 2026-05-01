<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        private StockService $stockService,
        private PaymentService $paymentService,
    ) {}

    public function create(array $data, array $items): Invoice
    {
        $data['invoice_number'] = $this->generateNumber();
        $data['share_token']    = Str::uuid();
        $data['created_by']     = auth()->id();
        $data['invoice_date']   = $data['invoice_date'] ?? now()->toDateString();

        $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $data['subtotal']      = $subtotal;
        $data['total_amount']  = $subtotal - ($data['discount_amount'] ?? 0) + ($data['tax_amount'] ?? 0);
        $data['paid_amount']   = 0;
        $data['status']        = 'pending';

        $invoice = Invoice::create(
            collect($data)->except(['items', 'initial_payment', 'payment_method'])->toArray()
        );

        foreach ($items as $item) {
            $invoice->items()->create([
                'product_size_id' => $item['product_size_id'],
                'product_name'    => $item['product_name'],
                'product_name_ur' => $item['product_name_ur'] ?? null,
                'size_label'      => $item['size_label'],
                'size_label_ur'   => $item['size_label_ur'] ?? null,
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'total_price'     => $item['quantity'] * $item['unit_price'],
            ]);

            $this->stockService->deduct($item['product_size_id'], $item['quantity'], $invoice);
        }

        if (!empty($data['initial_payment'])) {
            $this->paymentService->record($invoice, [
                'amount'         => $data['initial_payment'],
                'payment_method' => $data['payment_method'] ?? 'cash',
            ]);
        }

        return $invoice->fresh(['items', 'payments']);
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $last = Invoice::whereYear('created_at', $year)->max('id') ?? 0;

        return 'INV-' . $year . '-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }
}
