<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id');
            $table->string('model_name');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('type', 30)->default('legacy');
            $table->foreignId('target_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('source_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('received_by')->constrained('users');
            $table->date('payment_date')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Descriptive index name for polymorphic relationship
            $table->index(['model_id', 'model_name'], 'idx_payment_history_model');
            $table->index(['type', 'target_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};
