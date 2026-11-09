<?php

namespace Tests\Feature;

use App\Console\Commands\DatabaseBackupCommand;
use PHPUnit\Framework\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    public function test_it_batches_rows_into_multi_row_insert_statements(): void
    {
        $command = new DatabaseBackupCommand();
        $method = new \ReflectionMethod($command, 'buildInsertStatements');
        $method->setAccessible(true);

        $rows = [];
        for ($i = 1; $i <= 1001; $i++) {
            $rows[] = (object) [
                'id' => $i,
                'name' => 'user' . $i,
            ];
        }

        $pdo = new class {
            public function quote($value): string
            {
                return "'" . str_replace("'", "''", (string) $value) . "'";
            }
        };

        $statements = $method->invoke($command, 'users', $rows, $pdo);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('INSERT INTO `users`', $statements[0]);
        $this->assertSame(1000, substr_count($statements[0], '),(') + 1);
        $this->assertSame(1, substr_count($statements[1], '),(') + 1);
    }
}
