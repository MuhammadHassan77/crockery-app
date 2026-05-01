<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
