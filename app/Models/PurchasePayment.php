<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'paid_at',
        'received_by',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    /** Alias so views can use $payment->payment_date */
    public function getPaymentDateAttribute(): \Carbon\Carbon
    {
        return $this->paid_at;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return PaymentMethod::fromPaymentMethod($this->payment_method)->label();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
