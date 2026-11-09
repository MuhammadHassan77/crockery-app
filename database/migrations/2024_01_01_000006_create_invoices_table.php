<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->string('walk_in_name')->nullable();
                $table->string('walk_in_phone')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->nullable()->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->enum('status', ['paid', 'pending', 'partial'])->default('pending');
                $table->text('notes')->nullable();
                $table->integer('nug')->nullable();
                $table->decimal('nug_amount', 12, 2)->default(0);
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->string('share_token')->unique()->nullable();
                $table->decimal('exchange_item_amount', 12, 2)->default(0);
                $table->decimal('return_item_amount', 12, 2)->default(0);
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();

                $table->index(['status', 'invoice_date']);
                $table->index('invoice_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
