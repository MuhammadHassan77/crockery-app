<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index()
    {
        Artisan::call('customers:initialize-accounts');
        $backups = File::files(storage_path('app/backups'));

        $backups = array_values(array_filter($backups, static function ($file) {
            return $file->isFile();
        }));

        usort($backups, static function ($a, $b) {
            return $b->getMTime() <=> $a->getMTime();
        });

        return view('database-backups.index', compact('backups'));
    }

    public function store()
    // : RedirectResponse|BinaryFileResponse
    {
        try {
            Artisan::call('optimize:clear');
            $resultCode = Artisan::call('db:backup');

            if ($resultCode !== 0) {
                throw new \RuntimeException('Database backup command failed.');
            }

            return redirect()->back()->with('success', 'Backup created successfully.');
            return Response::download($result['path'], $result['filename'], ['Content-Type' => 'application/sql']);
        } catch (\Throwable $e) {
            Log::error('Backup creation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Backup creation failed. Please try again.');
        }
    }

    public function download(string $filename)
    {
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists($path)) {
            Log::warning('Backup download requested for missing file', ['filename' => $filename]);

            return redirect()->back()->with('error', 'Backup file was not found.');
        }

        return Response::download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backupFile' => $request->fromStorage == 1 ? 'required' : 'required|file|mimes:sql,txt|max:20480',
        ]);

        try {
            $file = $request->file('backupFile');
            if ($request->fromStorage == 1) {
                $filePath = storage_path('app/backups/' . $request->input('backupFile'));
                if (!File::exists($filePath)) {
                    return redirect()->back()->with('error', 'Backup file not found in storage.');
                }
                $file = new \Illuminate\Http\File($filePath);

            }
            $sql = file_get_contents($file->getRealPath());

            if ($sql === false || trim($sql) === '') {
                throw new \RuntimeException('The SQL file is empty or unreadable.');
            }

            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

            if ($statements === []) {
                throw new \RuntimeException('No SQL statements were found in the backup file.');
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tables = DB::select('SHOW TABLES');
            $dbName = DB::getDatabaseName();
            $key = 'Tables_in_' . $dbName;

            foreach ($tables as $table) {
                $tableName = $table->$key;
                DB::statement("DROP TABLE IF EXISTS `$tableName`");
            }

            foreach ($statements as $statement) {
                if ($statement !== '') {
                    DB::unprepared($statement);
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return redirect()->back()->with('success', 'Database restored successfully.');
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Log::error('Database restore failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Restore failed. The SQL file may be invalid or unsupported.');
        }
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists($path)) {
            Log::warning('Backup deletion requested for missing file', ['filename' => $filename]);

            return redirect()->back()->with('error', 'Backup file was not found.');
        }

        try {
            File::delete($path);

            return redirect()->back()->with('success', 'Backup deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Backup deletion failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete the backup file.');
        }
    }
}
