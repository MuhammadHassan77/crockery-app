<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    public function __invoke()
    {
        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_His') . '.sql';
        $path     = storage_path('app/' . $filename);

        $password = $dbPass ? "-p{$dbPass}" : '';
        $command  = "mysqldump -h {$dbHost} -u {$dbUser} {$password} {$dbName} > \"{$path}\"";

        exec($command, $output, $resultCode);

        if ($resultCode !== 0 || !file_exists($path)) {
            return back()->withErrors('Backup failed. Ensure mysqldump is in PATH.');
        }

        return Response::download($path, $filename, ['Content-Type' => 'application/sql'])
            ->deleteFileAfterSend(true);
    }
}
