<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseBackupCommand extends Command
{
    // protected $signature = 'database:backup {--manual}';
    // protected $description = 'Create a database backup';

    protected const INSERT_BATCH_SIZE = 1000;

    protected $signature = 'db:backup';
    protected $description = 'Create a SQL backup of the database';

    public function handle()
    {
        $db = DB::connection();
        $pdo = $db->getPdo();
        $dbName = $db->getDatabaseName();

        // $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $backupDir = storage_path('app/backups');

        // Make sure backup directory exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'db_backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($path, 'w');

        fwrite($handle, "-- Database backup: {$dbName}\n");
        fwrite($handle, "-- Created at: " . now() . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $dbName;

        foreach ($tables as $tableObj) {
            $table = $tableObj->$tableKey;

            $create = DB::select("SHOW CREATE TABLE `$table`");
            $createSql = $create[0]->{'Create Table'} ?? null;

            if (!$createSql) {
                continue;
            }

            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $createSql . ";\n\n");

            $rows = DB::table($table)->get()->all();

            foreach ($this->buildInsertStatements($table, $rows, $pdo) as $statement) {
                fwrite($handle, $statement . "\n");
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        // Delete backups older than 30 days
        // $deleteBefore = now()->subDays(30)->timestamp;

        // foreach (File::glob($backupDir . '/db_backup_*.sql') as $backupFile) {
        //     if (File::lastModified($backupFile) < $deleteBefore) {
        //         Log::info("Older Backup deleted: storage/app/backups/{$filename}");
        //         File::delete($backupFile);
        //     }
        // }

        $this->info("Backup created: storage/app/backups/{$filename}");
        Log::info("Backup created: storage/app/backups/{$filename}");

        return Command::SUCCESS;
        return ['status' => Command::SUCCESS, 'path' => $path, 'filename' => $filename];
    }

    protected function buildInsertStatements(string $table, array $rows, $pdo): array
    {
        if ($rows === []) {
            return [];
        }

        $firstRow = (array) $rows[0];
        $columns = array_keys($firstRow);
        $columnSql = implode(',', array_map(function ($column) {
            return "`{$column}`";
        }, $columns));

        $statements = [];

        foreach (array_chunk($rows, self::INSERT_BATCH_SIZE) as $batch) {
            $valueGroups = [];

            foreach ($batch as $row) {
                $rowArray = (array) $row;
                $values = [];

                foreach ($columns as $column) {
                    $value = $rowArray[$column] ?? null;
                    $values[] = is_null($value) ? 'NULL' : $pdo->quote($value);
                }

                $valueGroups[] = '(' . implode(',', $values) . ')';
            }

            $statements[] = "INSERT INTO `{$table}` ({$columnSql}) VALUES " . implode(',', $valueGroups) . ';';
        }

        return $statements;
    }
}
