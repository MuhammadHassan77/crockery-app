<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_size_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return']);
            $table->integer('quantity_change'); // negative for sale/deduction
            $table->integer('stock_after');     // balance after movement
            $table->nullableMorphs('reference'); // invoice, purchase order, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['product_size_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
