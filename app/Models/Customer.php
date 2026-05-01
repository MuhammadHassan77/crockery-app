<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'city', 'notes', 'is_active'];

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
        return $this->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->sum(\DB::raw('total_amount - paid_amount'));
    }
}
