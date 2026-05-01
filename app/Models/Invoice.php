<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'customer_id', 'walk_in_name', 'walk_in_phone',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'paid_amount',
        'status', 'notes', 'invoice_date', 'due_date', 'share_token', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->customer?->name ?? $this->walk_in_name ?? 'Walk-in Customer';
    }
}
