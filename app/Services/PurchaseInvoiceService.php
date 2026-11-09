<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\ProductSize;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private PurchaseStockService $purchaseStockService,
        private PurchasePaymentService $purchasePaymentService,
    ) {}

    public function create(array $data, array $items): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['invoice_number'] = $this->generateNumber();
            $data['share_token']    = Str::uuid();
            $data['created_by']     = auth()->id();
            $data['courier_service']   = $data['courier_service'] ?? null;
            $data['built_number']   = $data['built_number'] ?? null;
            $data['received_at']   = $data['received_at'] ?? null;
            $data['invoice_date']   = $data['invoice_date'] ?? now()->toDateString();

            $subtotal = collect($items)->sum(fn($i) => isset($i['weight']) && $i['weight'] > 0
                ? $i['weight'] * $i['unit_price']
                : $i['quantity'] * $i['unit_price']);
            $data['subtotal'] = $subtotal;


            $data['total_amount'] = $subtotal
                - ($data['discount_amount'] ?? 0)
                + ($data['tax_amount'] ?? 0);
            $data['paid_amount']   = 0;
            $data['status']        = 'pending';

            $invoice = PurchaseInvoice::create(
                collect($data)->except(['items', 'initial_payment', 'payment_method'])->toArray()
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

                // For purchase invoices we increase stock
                $this->purchaseStockService->purchase($item['product_size_id'], $item['quantity'], $data['notes'] ?? '', $item['unit_price']);
            }

            if (!empty($data['initial_payment'])) {
                $this->purchasePaymentService->record($invoice, [
                    'amount'         => $data['initial_payment'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                ]);
            }

            return $invoice->fresh(['items', 'purchase_payments']);
        });
    }

    /**
     * Update an existing invoice with new metadata and items.
     * Restores previous stock, recreates items, deducts new stock, optionally updates prices.
     */
    public function updatePurchaseInvoice(PurchaseInvoice $invoice, array $data, array $items, bool $updatePrices = false): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $items, $updatePrices) {
            // Reverse previous purchase stock additions
            foreach ($invoice->items as $old) {
                $this->purchaseStockService->reversePurchase($old->product_size_id, $old->quantity, $invoice);
            }

            // Remove old items
            $invoice->items()->delete();

            // Recompute totals
            $subtotal = collect($items)->sum(fn($i) => isset($i['weight']) && $i['weight'] > 0 ? $i['weight'] * $i['unit_price'] : $i['quantity'] * $i['unit_price']);
            $data['subtotal'] = $subtotal;


            $data['total_amount'] = $subtotal
                - ($data['discount_amount'] ?? 0)
                + ($data['tax_amount'] ?? 0);

            $invoice->update(collect($data)->except(['items', 'initial_payment', 'payment_method'])->toArray());

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

                // Add the new purchased quantities to stock
                $this->purchaseStockService->purchase($item['product_size_id'], $item['quantity'], $data['notes'] ?? '', $item['unit_price']);

                if ($updatePrices) {
                    $size = ProductSize::find($item['product_size_id']);
                    if ($size) {
                        app(\App\Services\PriceService::class)->setPrice($size, $item['unit_price']);
                    }
                }
            }

            // If initial payment provided and no payments exist, record it
            if (!empty($data['initial_payment']) && $invoice->purchase_payments()->count() === 1) {
                DB::table('purchase_payments')->where('purchase_invoice_id', $invoice?->id)->orderByDesc('updated_at')->delete();
                $this->purchasePaymentService->record($invoice, [
                    'amount'         => $data['initial_payment'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                ]);
            }

            // Recalculate invoice status based on payments
            $this->purchasePaymentService->syncPurchaseInvoiceStatus($invoice);

            return $invoice->fresh(['items', 'purchase_payments']);
        });
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $last = PurchaseInvoice::whereYear('created_at', $year)->max('id') ?? 0;

        return 'PR-INV-' . $year . '-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse stock additions for a full purchase invoice.
     * This will call the PurchaseStockService to decrement the quantities
     * previously added by the purchase invoice (e.g., before deleting an invoice).
     */
    public function reversePurchase(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                // reverse the stock that was added when this purchase invoice was created
                $this->purchaseStockService->reversePurchase($item->product_size_id, $item->quantity, $invoice);
            }
        });
    }
}
