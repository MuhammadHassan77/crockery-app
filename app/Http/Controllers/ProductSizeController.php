<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSize;
use App\Services\PriceService;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    public function __construct(private PriceService $priceService) {}

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'size_label'          => ['required', 'string', 'max:100'],
            'size_label_ur'       => ['nullable', 'string', 'max:100'],
            'sku'                 => ['nullable', 'string', 'unique:product_sizes,sku'],
            'stock_quantity'      => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'initial_price'       => ['required', 'numeric', 'min:0'],
        ]);

        $size = $product->sizes()->create($data);
        $this->priceService->setPrice($size, $data['initial_price']);

        return back()->with('success', 'Size added.');
    }

    public function update(Request $request, ProductSize $size)
    {
        $data = $request->validate([
            'size_label'          => ['required', 'string'],
            'size_label_ur'       => ['nullable', 'string', 'max:100'],
            'sku'                 => ['nullable', 'string'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active'           => ['boolean'],
        ]);

        $size->update($data);

        return back()->with('success', 'Size updated.');
    }

    public function setPrice(Request $request, ProductSize $size)
    {
        $request->validate(['price' => ['required', 'numeric', 'min:0']]);
        $this->priceService->setPrice($size, $request->price);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'new_price' => $request->price]);
        }

        return back()->with('success', 'Price updated.');
    }

    public function destroy(ProductSize $size)
    {
        $size->delete();
        return back()->with('success', 'Size removed.');
    }
}
