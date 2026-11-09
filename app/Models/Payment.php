<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'payment_method',
        'reference_number', 'notes', 'paid_at', 'received_by',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    /** Alias so views can use $payment->payment_date */
    public function getPaymentDateAttribute(): \Carbon\Carbon
    {
        return $this->paid_at;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return PaymentMethod::tryFrom($this->payment_method)?->label() ?? '';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function allocation(): HasOne
    {
        return $this->hasOne(CustomerPaymentAllocation::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
