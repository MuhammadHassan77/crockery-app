<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Vendor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'city', 'notes', 'is_active'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function purchase_invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function totalDue(): float
    {
        return $this->purchase_invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->sum(\DB::raw('total_amount - paid_amount'));
    }

    public function paymentHistories(): MorphMany
    {
        return $this->morphMany(
            PaymentHistory::class,
            'model',
            'model_name',
            'model_id'
        );
    }
}
