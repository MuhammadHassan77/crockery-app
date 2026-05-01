<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSize extends Model
{
    protected $fillable = [
        'product_id', 'size_label', 'size_label_ur', 'sku',
        'stock_quantity', 'low_stock_threshold', 'is_active',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class)->orderByDesc('changed_at');
    }

    public function currentPrice(): ?PriceHistory
    {
        return $this->priceHistory()->first();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
