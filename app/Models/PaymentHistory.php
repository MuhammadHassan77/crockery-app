<?php

namespace App\Models;

use App\Enums\PaymentHistoryType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentHistory extends Model
{
    use SoftDeletes;

    public $table = 'payment_history';

    public $fillable = [
        'model_id',
        'model_name',
        'amount',
        'received_by',
        'payment_method',
        'type',
        'target_invoice_id',
        'source_invoice_id',
        'payment_date',
        'notes',
    ];

    // protected $casts = ['paid_at' => 'datetime'];

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function model(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_name', 'model_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return PaymentHistoryType::tryFrom($this->type)?->label() ?? 'Unknown';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->type === PaymentHistoryType::Credit->value) {
            return PaymentHistoryType::Credit->label();
        }

        return PaymentMethod::tryFrom($this->payment_method ?? '')?->label() ?? '';
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class);
    }
    /*
    public function customer()
    {
        return $this->morphOne(Customer::class, 'model_id', 'model_name', 'model_id');
    }

    public function vendor()
    {
        return $this->morphOne(Vendor::class, 'model_id', 'model_name', 'model_id');
    }*/
}
