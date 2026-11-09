<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\ProductSize;

class PriceService
{
    public function setPrice(ProductSize $size, float $newPrice, ?float $purchasePrice = null): PriceHistory
    {
        $current = $size->currentPrice();

        return PriceHistory::create([
            'product_size_id' => $size->id,
            'purchase_price'  => $purchasePrice ?? $current?->purchase_price ?? null,
            'old_price'       => $current?->new_price,
            'new_price'       => $newPrice,
            'changed_by'      => auth()->id(),
            'changed_at'      => now(),
        ]);
    }
}
