<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    /**
     * Display the log file.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $file = $this->getLogFile($date);

        // dd($file, $file, File::exists($file), $logStack);
        if (!$file) {
            return view('logviewer', [
                'date' => $date,
                'logs' => null,
                'fileExists' => false,
                'logFile' => null,
            ]);
        }

        $logs = $this->getLatestLogs($file);

        return view('logviewer', [
            'date' => $date,
            'logs' => $logs,
            'fileExists' => true,
            'logFile' => basename($file),
        ]);
    }

    /**
     * Download the log file.
     */
    public function download(?string $date = null): BinaryFileResponse
    {
        $file = $this->getLogFile(
            $date ?? now()->format('Y-m-d')
        );

        if (!$file) {
            abort(404, 'Log file not found.');
        }

        return response()->download(
            $file,
            basename($file),
            [
                'Content-Type' => 'text/plain',
            ]
        );
    }

    /**
     * Resolve the correct log file.
     *
     * Supports:
     *
     * LOG_STACK=single
     * storage/logs/laravel.log
     *
     * LOG_STACK=daily
     * storage/logs/laravel-YYYY-MM-DD.log
     */
    private function getLogFile(?string $date = null): ?string
    {
        $logDirectory = storage_path('logs');

        $logStack = env('LOG_STACK', 'single');

        /*
         * Single log
         */
        if ($logStack === 'single') {
            $file = $logDirectory . '/laravel.log';

            return file_exists($file) ? $file : null;
        }

        /*
         * Daily logs
         */
        if ($logStack === 'daily') {
            try {
                $parsedDate = Carbon::createFromFormat(
                    'Y-m-d',
                    $date ?? now()->format('Y-m-d')
                );

                // Make sure the date is actually valid.
                if ($parsedDate->format('Y-m-d') !== $date) {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }

            $filename = 'laravel-' . $parsedDate->format('Y-m-d') . '.log';

            $file = $logDirectory . '/' . $filename;

            return file_exists($file) ? $file : null;
        }

        return null;
    }

    /**
     * Get log entries with the newest entry first.
     *
     * Keeps multi-line Laravel exceptions together.
     */
    private function getLatestLogs(string $file): string
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if ($lines === false || empty($lines)) {
            return '';
        }

        $entries = [];
        $currentEntry = '';

        foreach ($lines as $line) {

            // A new Laravel log entry starts with [YYYY-MM-DD HH:MM:SS]
            if (preg_match(
                '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
                $line
            )) {
                // Save previous entry
                if ($currentEntry !== '') {
                    $entries[] = $currentEntry;
                }

                // Start new entry
                $currentEntry = $line;
            } else {
                // Continuation of previous entry
                $currentEntry .= PHP_EOL . $line;
            }
        }

        // Save last entry
        if ($currentEntry !== '') {
            $entries[] = $currentEntry;
        }

        // Newest entry first
        $entries = array_reverse($entries);

        return implode(PHP_EOL . PHP_EOL, $entries);
    }
}
/*
class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Throwable $e) {
            abort(404);
        }

        $logStack = config('logging.default', 'stack');

        // Laravel's stack channel can use LOG_STACK=single/daily
        $stackChannel = config('logging.channels.stack.channels', []);

        $file = $this->getLogFile($parsedDate, $stackChannel);

        // dd($file, $file, File::exists($file), $logStack);
        if (!$file || !File::exists($file)) {
            return view('logviewer', [
                'date' => $date,
                'logs' => null,
                'fileExists' => false,
                'logFile' => $file,
            ]);
        }

        $logs = File::get($file);

        return view('logviewer', [
            'date' => $date,
            'logs' => $logs,
            'fileExists' => true,
            'logFile' => $file,
        ]);
    }

    public function download(?string $date = null): BinaryFileResponse
    {
        $file = $this->getLogFile($date);

        if (!$file) {
            abort(404, 'Log file not found.');
        }

        return response()->download(
            $file,
            basename($file),
            [
                'Content-Type' => 'text/plain',
            ]
        );
    }

    private function getLogFile(?string $date = null): ?string
    {
        $logDirectory = storage_path('logs');

        $channels = config('logging.channels.stack.channels', []);

        // LOG_STACK=single
        if (in_array('single', $channels, true)) {
            $file = $logDirectory . '/laravel.log';

            return file_exists($file) ? $file : null;
        }

        // LOG_STACK=daily
        if (in_array('daily', $channels, true)) {
            $date ??= now()->format('Y-m-d');

            try {
                $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Throwable $e) {
                return null;
            }

            $file = $logDirectory . '/laravel-' .
                $parsedDate->format('Y-m-d') . '.log';

            return file_exists($file) ? $file : null;
        }

        return null;
    }
}
*/