<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_payment_allocation_exclusions')) {
            Schema::create('customer_payment_allocation_exclusions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_history_id');
                $table->unsignedBigInteger('invoice_id');
                $table->timestamps();
            });
        }

        $foreignKeys = Schema::getForeignKeys('customer_payment_allocation_exclusions');
        $indexes = Schema::getIndexes('customer_payment_allocation_exclusions');
        $hasHistoryForeignKey = collect($foreignKeys)->contains(fn ($key) =>
            $key['columns'] === ['payment_history_id']
            && $key['foreign_table'] === 'payment_history'
            && $key['foreign_columns'] === ['id']
        );
        $hasInvoiceForeignKey = collect($foreignKeys)->contains(fn ($key) =>
            $key['columns'] === ['invoice_id']
            && $key['foreign_table'] === 'invoices'
            && $key['foreign_columns'] === ['id']
        );
        $hasUniqueIndex = collect($indexes)->contains(fn ($index) =>
            $index['unique']
            && $index['columns'] === ['payment_history_id', 'invoice_id']
        );

        if (! $hasHistoryForeignKey || ! $hasInvoiceForeignKey || ! $hasUniqueIndex) {
            Schema::table('customer_payment_allocation_exclusions', function (Blueprint $table) use (
                $hasHistoryForeignKey,
                $hasInvoiceForeignKey,
                $hasUniqueIndex,
            ) {
                if (! $hasHistoryForeignKey) {
                    $table->foreign('payment_history_id', 'cpae_history_fk')
                        ->references('id')->on('payment_history')->cascadeOnDelete();
                }
                if (! $hasInvoiceForeignKey) {
                    $table->foreign('invoice_id', 'cpae_invoice_fk')
                        ->references('id')->on('invoices')->cascadeOnDelete();
                }
                if (! $hasUniqueIndex) {
                    $table->unique(['payment_history_id', 'invoice_id'], 'cpae_history_invoice_unique');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_allocation_exclusions');
    }
};