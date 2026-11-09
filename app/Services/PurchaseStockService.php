<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\ProductSize;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseStockService
{
    public function deduct(int $productSizeId, int $quantity, PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($productSizeId, $quantity, $invoice) {
            $size = ProductSize::lockForUpdate()->findOrFail($productSizeId);
            $size->decrement('stock_quantity', $quantity);

            StockMovement::create([
                'product_size_id' => $size->id,
                'type'            => 'sale',
                'quantity_change' => -$quantity,
                'stock_after'     => $size->stock_quantity,
                'reference_id'    => $invoice->id,
                'reference_type'  => PurchaseInvoice::class,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    /**
     * Restore stock for a sale that is being reversed (e.g., invoice item removed).
     */
    public function restoreSale(int $productSizeId, int $quantity, PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($productSizeId, $quantity, $invoice) {
            $size = ProductSize::lockForUpdate()->findOrFail($productSizeId);
            $size->increment('stock_quantity', $quantity);

            StockMovement::create([
                'product_size_id' => $size->id,
                'type'            => 'sale_reversal',
                'quantity_change' => $quantity,
                'stock_after'     => $size->stock_quantity,
                'reference_id'    => $invoice->id,
                'reference_type'  => PurchaseInvoice::class,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    public function adjust(int $productSizeId, int $newQty, string $notes = '', ?float $purchasePrice = null): void
    {
        DB::transaction(function () use ($productSizeId, $newQty, $notes, $purchasePrice) {
            $size  = ProductSize::lockForUpdate()->findOrFail($productSizeId);
            $diff  = $newQty - $size->stock_quantity;

            $size->update(['stock_quantity' => $newQty]);

            StockMovement::create([
                'product_size_id' => $size->id,
                'type'            => 'adjustment',
                'quantity_change' => $diff,
                'stock_after'     => $newQty,
                'notes'           => $notes ?? '',
                'purchase_price'  => $purchasePrice,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    public function purchase(int $productSizeId, int $quantity, string $notes = '', ?float $purchasePrice = null): void
    {
        DB::transaction(function () use ($productSizeId, $quantity, $notes, $purchasePrice) {
            $size = ProductSize::with('product')->lockForUpdate()->findOrFail($productSizeId);
            if (!$size->product->is_weighted) {
                $size->increment('stock_quantity', $quantity);

                StockMovement::create([
                    'product_size_id' => $size->id,
                    'type'            => 'purchase',
                    'quantity_change' => $quantity,
                    'stock_after'     => $size->stock_quantity,
                    'notes'           => $notes ?? '',
                    'purchase_price'  => $purchasePrice,
                    'created_by'      => auth()->id(),
                ]);
            }
        });
    }

    /**
     * Reverse stock added by a purchase invoice (used when invoice/items are removed).
     */
    public function reversePurchase(int $productSizeId, int $quantity, PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($productSizeId, $quantity, $invoice) {
            $size = ProductSize::with('product')->lockForUpdate()->findOrFail($productSizeId);

            if (!$size->product->is_weighted) {
                $size->decrement('stock_quantity', $quantity);
                StockMovement::create([
                    'product_size_id' => $size->id,
                    'type'            => 'purchase_reversal',
                    'quantity_change' => -$quantity,
                    'stock_after'     => $size->stock_quantity,
                    'reference_id'    => $invoice->id,
                    'reference_type'  => PurchaseInvoice::class,
                    'created_by'      => auth()->id(),
                ]);
            }
        });
    }
}
