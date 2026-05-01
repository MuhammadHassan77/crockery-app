<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_size_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('new_price', 10, 2);
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['product_size_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
