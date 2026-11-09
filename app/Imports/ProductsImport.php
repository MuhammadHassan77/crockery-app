<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    use Importable;
    public array $productMapping = [];
    public $categories = [];

    public function __construct()
    {
        $this->categories = Category::all()->keyBy('name');
        // dd($this);
    }
    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            foreach ($rows as $row) {
                // dd($row, $this->categories);

                $excelProductId = (int)$row['productid'];
                $productName = trim($row['productname']);
                $is_weighted = trim($row['is_weighted']);


                $category = $this->categories->first(function ($category) use ($productName) {
                    return str_contains($productName, $category->name);
                });


                if (!$productName) {
                    continue;
                }

                $slug = Str::slug($productName);

                if (Product::where('slug', $slug)->exists()) {
                    $slug .= '-' . uniqid();
                }

                $product = Product::firstOrCreate(
                    [
                        'name' => $productName,
                    ],
                    [
                        'category_id' => $category?->id ?? 9,
                        'name_ur' => $productName,
                        'slug' => $slug,
                        'description' => null,
                        'image' => null,
                        'is_active' => true,
                        'is_weighted' => $is_weighted ?? 0,
                    ]
                );
                // $product = Product::where('name', $productName)->first();

                $this->productMapping[$excelProductId] = $product?->id;
            }
        });
    }
}
