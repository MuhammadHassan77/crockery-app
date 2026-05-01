<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Urdu name for products
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_ur')->nullable()->after('name')
                  ->comment('Urdu/Roman-Urdu product name for bilingual invoices');
        });

        // Urdu size label for product_sizes
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->string('size_label_ur')->nullable()->after('size_label')
                  ->comment('Urdu size label e.g. چھوٹا / 6 انچ');
        });

        // Snapshot Urdu names on invoice items (so old invoices still print correctly)
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('product_name_ur')->nullable()->after('product_name');
            $table->string('size_label_ur')->nullable()->after('size_label');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('name_ur');
        });
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->dropColumn('size_label_ur');
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['product_name_ur', 'size_label_ur']);
        });
    }
};
