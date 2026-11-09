<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentAllocation extends Model
{
    protected $fillable = ['payment_history_id', 'payment_id', 'amount'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PaymentHistory::class, 'payment_history_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}