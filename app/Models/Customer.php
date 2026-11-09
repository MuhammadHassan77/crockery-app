<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'city', 'notes', 'is_active', 'account_balance'];

    protected $casts = ['account_balance' => 'decimal:2'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function totalDue(): float
    {
        return (float) $this->account_balance;
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

    public function accountReceipts(): MorphMany
    {
        return $this->paymentHistories()->whereIn('type', ['receipt', 'credit']);
    }
}
