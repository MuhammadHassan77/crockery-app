<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with(['category', 'sizes'])
                ->select('products.*');

            return DataTables::of($products)
                ->addColumn('category', fn($p) => $p->category?->name ?? '—')
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
        $data = $request->validate([
            'category_id'                    => ['required', 'exists:categories,id'],
            'name'                           => ['required', 'string', 'max:255'],
            'name_ur'                        => ['nullable', 'string', 'max:255'],
            'description'                    => ['nullable', 'string'],
            'image'                          => ['nullable', 'image', 'max:2048'],
            'sizes'                          => ['required', 'array', 'min:1'],
            'sizes.*.size_label'             => ['required', 'string', 'max:100'],
            'sizes.*.size_label_ur'          => ['nullable', 'string', 'max:100'],
            'sizes.*.sku'                    => ['nullable', 'string'],
            'sizes.*.stock_quantity'         => ['nullable', 'integer', 'min:0'],
            'sizes.*.low_stock_threshold'    => ['nullable', 'integer', 'min:0'],
            'sizes.*.initial_price'          => ['required', 'numeric', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $product = Product::create(\Arr::except($data, ['sizes']));

        foreach ($data['sizes'] as $sizeData) {
            $size = $product->sizes()->create([
                'size_label'          => $sizeData['size_label'],
                'size_label_ur'       => $sizeData['size_label_ur'] ?? null,
                'sku'                 => $sizeData['sku'] ?: null,
                'stock_quantity'      => $sizeData['stock_quantity'] ?? 0,
                'low_stock_threshold' => $sizeData['low_stock_threshold'] ?? 5,
            ]);
            app(\App\Services\PriceService::class)->setPrice($size, $sizeData['initial_price']);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['sizes.priceHistory', 'category']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'name_ur'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'max:2048'],
            'is_active'   => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }
}
