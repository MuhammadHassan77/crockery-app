<?php

namespace App\Http\Controllers;

use App\Models\ProductSize;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function adjust(Request $request, ProductSize $size)
    {
        try {
            $request->validate([
                'quantity'       => ['required', 'integer', 'min:0'],
                'notes'          => ['nullable', 'string'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
            ]);

            $this->stockService->adjust($size->id, $request->quantity, $request->notes ?? '', $request->purchase_price);
            $size->refresh();

            if ($request->ajax()) {
                return response()->json([
                    'new_stock' => $size->stock_quantity,
                    'is_low'    => $size->isLowStock(),
                ]);
            }

            return back()->with('success', 'Stock adjusted.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to adjust stock. Please try again.');
        }
    }

    public function purchase(Request $request, ProductSize $size)
    {
        $request->validate([
            'quantity'       => ['required', 'integer', 'min:1'],
            'notes'          => ['nullable', 'string'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->stockService->purchase($size->id, $request->quantity, $request->notes ?? '', $request->purchase_price);
        $size->refresh();

        if ($request->ajax()) {
            return response()->json([
                'new_stock' => $size->stock_quantity,
                'is_low'    => $size->isLowStock(),
            ]);
        }

        return back()->with('success', "Added {$request->quantity} units to stock.");
    }
}
