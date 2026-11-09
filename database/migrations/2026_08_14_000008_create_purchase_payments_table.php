<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_payments')) {
            Schema::create('purchase_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->enum('payment_method', ['cash', 'bank', 'jazzcash', 'easypaisa', 'cheque', 'other'])->default('cash');
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at');
                $table->foreignId('received_by')->constrained('users');
                $table->timestamps();

                $table->index('purchase_invoice_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
