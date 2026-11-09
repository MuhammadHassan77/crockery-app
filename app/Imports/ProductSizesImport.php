<?php

namespace App\Imports;

use App\Models\ProductSize;
use App\Models\PriceHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductSizesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    use Importable;
    protected array $productMapping;

    public function __construct(array $productMapping)
    {
        $this->productMapping = $productMapping;
    }

    public function chunkSize(): int
    {
        return 500;
    }
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            foreach ($rows as $row) {
                // dd($row);

                $excelProductId = (int)$row['productid'];

                if (!isset($this->productMapping[$excelProductId])) {
                    continue;
                }

                $productId = $this->productMapping[$excelProductId];

                $size = ProductSize::firstOrCreate(
                    [
                        'product_id' => $productId,
                        'size_label' => trim($row['sizename']),
                    ],
                    [
                        'sku' => null,
                        'size_label_ur' => trim($row['sizename']),
                        'stock_quantity' => 0,
                        'low_stock_threshold' => 5,
                        'is_active' => true,
                    ]
                );

                PriceHistory::create([
                    'product_size_id' => $size->id,
                    'purchase_price' => $row['purchaseprice'] ?? 0,
                    'old_price' => null,
                    'new_price' => $row['sellprice'] ?? 0,
                    'changed_by' => auth()?->user()?->id,
                    'changed_at' => now(),
                ]);
            }
        });
    }
}
