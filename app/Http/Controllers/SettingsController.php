<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private array $keys = [
        'shop_name', 'shop_tagline', 'shop_address',
        'shop_city', 'shop_phone', 'shop_phone2',
        'shop_email', 'bill_footer_note', 'low_stock_default',
    ];

    public function edit()
    {
        $settings = Setting::getMany($this->keys);
        return view('settings.shop', compact('settings'));
    }

    public function preview()
    {
        // Show a blank sample invoice using current settings
        $invoice = \App\Models\Invoice::with(['items', 'payments', 'customer'])
            ->latest()->first();

        if (! $invoice) {
            return redirect()->route('settings.edit')
                ->with('error', 'No invoices found to preview. Create an invoice first.');
        }

        $previousBalance = 0;
        return view('invoices.print', compact('invoice', 'previousBalance'));
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'shop_name'         => ['required', 'string', 'max:150'],
                'shop_tagline'      => ['nullable', 'string', 'max:200'],
                'shop_address'      => ['nullable', 'string', 'max:300'],
                'shop_city'         => ['nullable', 'string', 'max:100'],
                'shop_phone'        => ['nullable', 'string', 'max:50'],
                'shop_phone2'       => ['nullable', 'string', 'max:50'],
                'shop_email'        => ['nullable', 'email', 'max:150'],
                'bill_footer_note'  => ['nullable', 'string', 'max:500'],
                'low_stock_default' => ['nullable', 'integer', 'min:0', 'max:9999'],
            ]);

            Setting::setMany($validated);

            return back()->with('success', __('Settings saved successfully.'));
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to save settings. Please try again.');
        }
    }
}
