<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_sizes')) {
            Schema::create('product_sizes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('size_label');     // e.g. "Small", "12-inch", "250ml"
                $table->string('size_label_ur')->nullable()->comment('Urdu size label e.g. چھوٹا / 6 انچ');
                $table->string('sku')->unique()->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->integer('low_stock_threshold')->default(5);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('size_label');
                $table->index('sku');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};
