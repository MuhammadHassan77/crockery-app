<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_size_id', 'product_name', 'product_name_ur',
        'size_label', 'size_label_ur', 'quantity', 'unit_price', 'weight', 'total_price',
    ];

    public function purchase_invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }
}
