<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default values
        DB::table('settings')->insert([
            ['key' => 'shop_name',        'value' => config('app.name', 'Bartan Shop'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_tagline',     'value' => 'Crockery & Household Items',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_address',     'value' => config('app.shop_address', ''),   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_city',        'value' => '',                                'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_phone',       'value' => config('app.shop_phone', ''),     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_phone2',      'value' => '',                                'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shop_email',       'value' => '',                                'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bill_footer_note', 'value' => 'شکریہ — خریداری کرنے کا شکریہ!','created_at' => now(), 'updated_at' => now()],
            ['key' => 'low_stock_default','value' => '5',                               'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
