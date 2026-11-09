<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSize;
use App\Http\Controllers\Traits\SelectFieldTrait;
use App\Models\InvoiceItem;
use App\Models\PurchaseInvoiceItem;
use App\Services\PriceService;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    use SelectFieldTrait;

    public function __construct(private PriceService $priceService) {}

    public function store(Request $request, Product $product)
    {
        try {
            $data = $request->validate([
                'size_label'          => ['required', 'string', 'max:100'],
                'size_label_ur'       => ['nullable', 'string', 'max:100'],
                'sku'                 => ['nullable', 'string', 'unique:product_sizes,sku'],
                'stock_quantity'      => ['required', 'integer', 'min:0'],
                'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
                'initial_price'       => ['required', 'numeric', 'min:0'],
                'purchase_price'      => ['nullable', 'numeric', 'min:0'],
            ]);

            $purchasePrice = $data['purchase_price'] ?? null;
            unset($data['purchase_price']);

            $size = $product->sizes()->create($data);
            $this->priceService->setPrice($size, $data['initial_price'], $purchasePrice);

            return back()->with('success', 'Size added.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to add size. Please try again.');
        }
    }

    public function update(Request $request, ProductSize $size)
    {
        try {
            $data = $request->validate([
                'size_label'          => ['required', 'string'],
                'size_label_ur'       => ['nullable', 'string', 'max:100'],
                'sku'                 => ['nullable', 'string'],
                'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
                'is_active'           => ['nullable'],
            ]);

            $size->update($data);

            return response()->json(['message'=> 'Size updated.']);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to update size. Please try again.');
        }
    }

    public function setPrice(Request $request, ProductSize $size)
    {
        try {
            $request->validate([
                'price'          => ['required', 'numeric', 'min:0'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
            ]);

            $this->priceService->setPrice($size, $request->price, $request->purchase_price);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'new_price' => $request->price]);
            }

            return back()->with('success', 'Price updated.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to update price. Please try again.');
        }
    }

    public function destroy(ProductSize $size)
    {
        try {
            if (InvoiceItem::where('product_size_id', $size?->id)->exists() || PurchaseInvoiceItem::where('product_size_id', $size?->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Size is in use, can\'t delete it.']);
            }

            $size->delete();
            return response()->json(['success' => true, 'message' => 'Size removed.']);
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to remove size. Please try again.');
        }
    }

    /**
     * Search product sizes for Select2 dropdown
     * Supports search by size label or SKU with pagination
     */
    public function search(Request $request)
    {
        try {

            $request->validate([
                'q'    => ['nullable', 'string', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1']
            ]);

            $query = $this->sanitizeSearchQuery($request->q);
            $page = $request->page ?? 1;

            // Build search query - search in product name, size label, and sku
            $sizes = ProductSize::with('product')
                ->where(function ($q) use ($query) {
                    $q->where('id', $query)
                        ->orWhere('size_label', 'like', "%$query%")
                        ->orWhere('sku', 'like', "%$query%")
                        ->orWhereHas('product', function ($subQuery) use ($query) {
                            $subQuery->where('name', 'like', "%$query%");
                        });
                })
                ->where('is_active', true)
                ->when(request()->editMode, function ($q) {
                    $q->orderBy('id');
                })
                ->orderBy('size_label')
                ->select('id', 'product_id', 'size_label', 'size_label_ur', 'sku', 'stock_quantity');

            // Format the results using custom formatter
            $results = $this->formatSelectResults($sizes, $page, function ($size) {
                $price = optional($size->currentPrice())->new_price ?? 0;
                $purchase_price = optional($size->currentPrice())->purchase_price ?? 0;
                // $text = "{$size->product->name} - {$size->size_label}";
                $text = $size->size_label;
                if ($size->sku) $text .= " ({$size->sku})";
                // $text .= " [Stock: {$size->stock_quantity}]";

                return [
                    'id'    => $size->id,
                    'text'  => $text,
                    'price' => $price,
                    'is_weighted' => $size?->product?->is_weighted,
                    'purchase_price' => $purchase_price,
                    'stock' => $size->stock_quantity,
                    'product_name' => $size->product->name,
                    'product_name_ur' => $size->product->name_ur ?? '',
                    'size_label' => $size->size_label,
                    'size_label_ur' => $size->size_label_ur ?? '',
                ];
            });

            return response()->json($results);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to search product sizes. Please try again.');
        }
    }
}
