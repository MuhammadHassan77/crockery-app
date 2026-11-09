<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('phone')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->decimal('account_balance', 14, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
