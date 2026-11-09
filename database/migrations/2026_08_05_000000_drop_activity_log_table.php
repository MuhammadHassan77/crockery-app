<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('activitylog.table_name', 'activity_log');
        $connection = config('activitylog.database_connection', config('database.default'));

        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Do not recreate the activity log table here, as the schema is no longer maintained.
    }
};
