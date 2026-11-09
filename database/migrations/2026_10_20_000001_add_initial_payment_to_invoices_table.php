<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('initial_payment', 12, 2)->default(0)->after('paid_amount');
        });

        DB::table('invoices')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('payment_history')
                    ->whereColumn('payment_history.source_invoice_id', 'invoices.id')
                    ->where('payment_history.type', 'receipt')
                    ->whereNull('payment_history.deleted_at');
            })
            ->update([
                'initial_payment' => DB::raw('(SELECT COALESCE(SUM(payment_history.amount), 0) FROM payment_history WHERE payment_history.source_invoice_id = invoices.id AND payment_history.type = \'receipt\' AND payment_history.deleted_at IS NULL)'),
            ]);

        DB::table('invoices')
            ->whereNull('customer_id')
            ->update([
                'initial_payment' => DB::raw('(SELECT COALESCE((SELECT payments.amount FROM payments WHERE payments.invoice_id = invoices.id ORDER BY payments.id LIMIT 1), 0))'),
            ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('initial_payment');
        });
    }
};