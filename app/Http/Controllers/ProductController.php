<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Http\Controllers\Traits\SelectFieldTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Imports\ProductSizesImport;
use App\Imports\ProductsImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    use SelectFieldTrait;
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with(['sizes'])
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->select('products.*', 'categories.name as category_name')
                ->when($request->category_id, fn($q) => $q->where('products.category_id', request()->category_id));

            return DataTables::of($products)
                ->addColumn('category', fn($p) => $p->category_name ?? ($p->category?->name ?? '—'))
                ->orderColumn('category', 'category_name $1')
                ->addColumn('sizes_count', fn($p) => $p->sizes->count())
                ->addColumn('stock', fn($p) => $p->sizes->sum('stock_quantity'))
                ->addColumn('status', fn($p) => $p->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>')
                ->addColumn('actions', fn($p) => '
                    <a href="'.route('products.show', $p).'" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="'.route('products.edit', $p).'" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="'.$p->id.'" data-url="'.route('products.destroy', $p).'" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('products.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::active()->orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'category_id'                    => ['required', 'exists:categories,id'],
                'name'                           => ['required', 'string', 'max:255'],
                'name_ur'                        => ['nullable', 'string', 'max:255'],
                'description'                    => ['nullable', 'string'],
                'is_weighted'                    => ['nullable', 'in:1,0'],
                'image'                          => ['nullable', 'image', 'max:2048'],
                'sizes'                          => ['required', 'array', 'min:1'],
                'sizes.*.size_label'             => ['required', 'string', 'max:100'],
                'sizes.*.size_label_ur'          => ['nullable', 'string', 'max:100'],
                'sizes.*.sku'                    => ['nullable', 'string'],
                'sizes.*.stock_quantity'         => ['nullable', 'integer', 'min:0'],
                'sizes.*.low_stock_threshold'    => ['nullable', 'integer', 'min:0'],
                'sizes.*.purchase_price'         => ['nullable', 'numeric', 'min:0'],
                'sizes.*.initial_price'          => ['required', 'numeric', 'min:0'],
            ]);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            $product = DB::transaction(function () use ($data) {
                $data['slug'] = Str::slug($data['name']) . md5(uniqid(rand(), true));
                $product = Product::create(Arr::except($data, ['sizes']));

                foreach ($data['sizes'] as $sizeData) {
                    $size = $product->sizes()->create([
                        'size_label'          => $sizeData['size_label'],
                        'size_label_ur'       => $sizeData['size_label_ur'] ?? null,
                        'sku'                 => $sizeData['sku'] ?: null,
                        'stock_quantity'      => $sizeData['stock_quantity'] ?? 0,
                        'low_stock_threshold' => $sizeData['low_stock_threshold'] ?? 5,
                    ]);
                    app(\App\Services\PriceService::class)->setPrice($size, $sizeData['initial_price'], $sizeData['purchase_price'] ?? 0);
                }

                return $product;
            });

            return redirect()->route('products.show', $product)->with('success', 'Product created successfully.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create product. Please try again.');
        }
    }

    public function show(Product $product)
    {
        $product->load(['sizes.priceHistory', 'category']);
        return view('products.show', compact('product'));
    }

    /**
     * Search products for Select2 dropdown
     * Supports search by name or SKU with pagination
     */
    public function search(Request $request)
    {
        $request->validate([
            'q'    => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1']
        ]);

        $query = $this->sanitizeSearchQuery($request->q);
        $page = $request->page ?? 1;

        // Build search query
        $products = Product::where(function ($q) use ($query) {
            $q->where('name', 'like', "%$query%")
              ->orWhere('sku', 'like', "%$query%");
        })
        ->where('is_active', true)
        ->orderBy('name')
        ->select('id', 'name', 'sku');

        // Format the results using custom formatter
        $results = $this->formatSelectResults($products, $page, function ($product) {
            return [
                'id'   => $product->id,
                'text' => "{$product->name}" . ($product->sku ? " ({$product->sku})" : "")
            ];
        });

        return response()->json($results);
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        try {
            $data = $request->validate([
                'category_id' => ['required', 'exists:categories,id'],
                'name'        => ['required', 'string', 'max:255'],
                'name_ur'     => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_weighted' => ['nullable', 'in:1,0'],
                'image'       => ['nullable', 'image', 'max:2048'],
                'is_active'   => ['boolean'],
            ]);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('products', 'public');
            }
            $data['is_active'] = $request?->is_active ?? false;
            $data['is_weighted'] = $request?->is_weighted ?? false;

            $product->update($data);

            return redirect()->route('products.show', $product)->with('success', 'Product updated.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to update product. Please try again.');
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Product deleted.');
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete product. Please try again.');
        }
    }


    public function import(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $productsImport = new ProductsImport();

                Excel::import($productsImport, storage_path('app/products.xlsx'), null, \Maatwebsite\Excel\Excel::XLSX);
                Excel::import(new ProductSizesImport($productsImport->productMapping), storage_path('app/product-sizes.xlsx'), null, \Maatwebsite\Excel\Excel::XLSX);
            });

            return response()->json(['message' => 'Products and sizes imported successfully.']);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to import products. Please try again.', null, 422);
        }
    }
}
