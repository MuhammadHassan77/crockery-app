<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\ProductSize;

class PriceService
{
    public function setPrice(ProductSize $size, float $newPrice): PriceHistory
    {
        $current = $size->currentPrice();

        return PriceHistory::create([
            'product_size_id' => $size->id,
            'old_price'       => $current?->new_price,
            'new_price'       => $newPrice,
            'changed_by'      => auth()->id(),
            'changed_at'      => now(),
        ]);
    }
}
