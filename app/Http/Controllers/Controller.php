<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    protected function handleException(Request $request, \Throwable $e, string $message, string $redirectUrl = null, int $status = 500)
    {
        Log::error($e);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        $response = $redirectUrl ? redirect($redirectUrl) : back()->withInput();
        return $response->with('error', "$message | error : " . $e->getMessage());
    }
}
