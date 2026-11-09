<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_invoice_items')) {
            Schema::create('purchase_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_size_id')->constrained();
                $table->string('product_name');     // snapshot at sale time
                $table->string('size_label');        // snapshot at sale time
                $table->string('product_name_ur')->nullable();     // snapshot at sale time
                $table->string('size_label_ur')->nullable();        // snapshot at sale time
                $table->integer('quantity');
                $table->decimal('weight', 10, 2)->nullable();
                $table->decimal('unit_price', 10, 2); // snapshot at sale time
                $table->decimal('total_price', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
