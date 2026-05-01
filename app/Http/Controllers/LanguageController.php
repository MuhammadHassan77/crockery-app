<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['en', 'ur']), 404);
        session(['locale' => $locale]);
        return back();
    }
}
