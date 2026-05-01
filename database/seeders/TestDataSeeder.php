<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    private User $admin;

    public function run(): void
    {
        $this->admin = User::where('is_admin', true)->first() ?? User::first();
        Auth::login($this->admin);

        $this->seedCategories();
        $this->seedProducts();
        $this->seedCustomers();
        $this->seedInvoices();

        $this->command->info('Test data seeded successfully.');
    }

    private function seedCategories(): void
    {
        $categories = ['Dinner Sets', 'Cups & Mugs', 'Cooking Pots', 'Serving Items', 'Storage Jars', 'Cutlery'];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name], [
                'slug'      => \Illuminate\Support\Str::slug($name),
                'is_active' => true,
            ]);
        }

        $this->command->line('  Categories seeded.');
    }

    private function seedProducts(): void
    {
        $dinnerSet  = Category::where('name', 'Dinner Sets')->first();
        $cups       = Category::where('name', 'Cups & Mugs')->first();
        $pots       = Category::where('name', 'Cooking Pots')->first();
        $serving    = Category::where('name', 'Serving Items')->first();
        $storage    = Category::where('name', 'Storage Jars')->first();
        $cutlery    = Category::where('name', 'Cutlery')->first();

        $products = [
            [
                'name' => 'Bone China Dinner Set',
                'category_id' => $dinnerSet->id,
                'description' => 'Premium bone china 24-piece dinner set',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '12 Pieces', 'stock_quantity' => 15, 'low_stock_threshold' => 3, 'price' => 2800],
                    ['size_label' => '24 Pieces', 'stock_quantity' => 8,  'low_stock_threshold' => 2, 'price' => 5200],
                    ['size_label' => '36 Pieces', 'stock_quantity' => 4,  'low_stock_threshold' => 2, 'price' => 7500],
                ],
            ],
            [
                'name' => 'Melamine Dinner Set',
                'category_id' => $dinnerSet->id,
                'description' => 'Durable melamine set, ideal for everyday use',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '6 Pieces',  'stock_quantity' => 25, 'low_stock_threshold' => 5,  'price' => 850],
                    ['size_label' => '12 Pieces', 'stock_quantity' => 18, 'low_stock_threshold' => 4,  'price' => 1600],
                ],
            ],
            [
                'name' => 'Ceramic Tea Set',
                'category_id' => $cups->id,
                'description' => 'Handcrafted ceramic tea cups with saucers',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '6 Cup Set',  'stock_quantity' => 20, 'low_stock_threshold' => 4, 'price' => 950],
                    ['size_label' => '12 Cup Set', 'stock_quantity' => 12, 'low_stock_threshold' => 3, 'price' => 1800],
                ],
            ],
            [
                'name' => 'Glass Mugs Set',
                'category_id' => $cups->id,
                'description' => 'Heat-resistant borosilicate glass mugs',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '6 Pieces', 'stock_quantity' => 30, 'low_stock_threshold' => 6,  'price' => 720],
                ],
            ],
            [
                'name' => 'Aluminium Degchi',
                'category_id' => $pots->id,
                'description' => 'Heavy gauge aluminium cooking pot',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '1 Litre',   'stock_quantity' => 20, 'low_stock_threshold' => 5,  'price' => 380],
                    ['size_label' => '2 Litre',   'stock_quantity' => 18, 'low_stock_threshold' => 5,  'price' => 620],
                    ['size_label' => '5 Litre',   'stock_quantity' => 10, 'low_stock_threshold' => 3,  'price' => 1100],
                    ['size_label' => '10 Litre',  'stock_quantity' => 2,  'low_stock_threshold' => 3,  'price' => 1900],
                ],
            ],
            [
                'name' => 'Non-Stick Karahi',
                'category_id' => $pots->id,
                'description' => 'Granite-coated non-stick karahi wok',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => 'Small (20cm)',  'stock_quantity' => 12, 'low_stock_threshold' => 3, 'price' => 1200],
                    ['size_label' => 'Medium (26cm)', 'stock_quantity' => 9,  'low_stock_threshold' => 3, 'price' => 1650],
                    ['size_label' => 'Large (30cm)',  'stock_quantity' => 5,  'low_stock_threshold' => 3, 'price' => 2100],
                ],
            ],
            [
                'name' => 'Stainless Steel Serving Tray',
                'category_id' => $serving->id,
                'description' => 'Mirror-finish stainless steel serving tray',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => 'Small',  'stock_quantity' => 22, 'low_stock_threshold' => 5, 'price' => 450],
                    ['size_label' => 'Medium', 'stock_quantity' => 17, 'low_stock_threshold' => 5, 'price' => 680],
                    ['size_label' => 'Large',  'stock_quantity' => 10, 'low_stock_threshold' => 4, 'price' => 950],
                ],
            ],
            [
                'name' => 'Plastic Storage Containers',
                'category_id' => $storage->id,
                'description' => 'Air-tight food storage containers set',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '3-Piece Set',  'stock_quantity' => 35, 'low_stock_threshold' => 8, 'price' => 320],
                    ['size_label' => '6-Piece Set',  'stock_quantity' => 25, 'low_stock_threshold' => 6, 'price' => 580],
                    ['size_label' => '12-Piece Set', 'stock_quantity' => 1,  'low_stock_threshold' => 4, 'price' => 1050],
                ],
            ],
            [
                'name' => 'Stainless Steel Cutlery Set',
                'category_id' => $cutlery->id,
                'description' => '18/10 stainless steel cutlery set',
                'is_active' => true,
                'sizes' => [
                    ['size_label' => '12 Pieces', 'stock_quantity' => 15, 'low_stock_threshold' => 4, 'price' => 1400],
                    ['size_label' => '24 Pieces', 'stock_quantity' => 9,  'low_stock_threshold' => 3, 'price' => 2600],
                    ['size_label' => '48 Pieces', 'stock_quantity' => 3,  'low_stock_threshold' => 2, 'price' => 4800],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $sizes = $productData['sizes'];
            unset($productData['sizes']);

            $productData['slug'] = \Illuminate\Support\Str::slug($productData['name']);
            $product = Product::firstOrCreate(['name' => $productData['name']], $productData);

            foreach ($sizes as $sizeData) {
                $price = $sizeData['price'];
                unset($sizeData['price']);

                $size = ProductSize::firstOrCreate(
                    ['product_id' => $product->id, 'size_label' => $sizeData['size_label']],
                    array_merge($sizeData, ['product_id' => $product->id, 'is_active' => true])
                );

                // Seed price history if no price exists
                if ($size->priceHistory()->count() === 0) {
                    $size->priceHistory()->create([
                        'new_price'  => $price,
                        'old_price'  => null,
                        'changed_by' => $this->admin->id,
                        'changed_at' => now(),
                    ]);
                }
            }
        }

        $this->command->line('  Products & sizes seeded.');
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['name' => 'Ahmed Ali',         'phone' => '03001234567', 'city' => 'Karachi',   'email' => 'ahmed@example.com',   'is_active' => true],
            ['name' => 'Muhammad Usman',    'phone' => '03121234567', 'city' => 'Lahore',    'email' => null,                   'is_active' => true],
            ['name' => 'Fatima Zahra',      'phone' => '03331234567', 'city' => 'Islamabad', 'email' => 'fatima@example.com',  'is_active' => true],
            ['name' => 'Tariq Mehmood',     'phone' => '03451234567', 'city' => 'Faisalabad','email' => null,                   'is_active' => true],
            ['name' => 'Nadia Hussain',     'phone' => '03211234567', 'city' => 'Multan',    'email' => 'nadia@example.com',   'is_active' => true],
            ['name' => 'Hassan Raza',       'phone' => '03111234567', 'city' => 'Karachi',   'email' => null,                   'is_active' => true],
            ['name' => 'Zainab Khan',       'phone' => '03041234567', 'city' => 'Peshawar',  'email' => 'zainab@example.com',  'is_active' => true],
            ['name' => 'Bilal Shahid',      'phone' => '03551234567', 'city' => 'Rawalpindi','email' => null,                   'is_active' => false],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(['phone' => $customer['phone']], $customer);
        }

        $this->command->line('  Customers seeded.');
    }

    private function seedInvoices(): void
    {
        if (Invoice::count() > 0) {
            $this->command->line('  Invoices already exist, skipping.');
            return;
        }

        $service   = app(InvoiceService::class);
        $customers = Customer::where('is_active', true)->get();
        $sizes     = ProductSize::where('is_active', true)->with('product')->get();

        $scenarios = [
            // [customer_index_or_null, payment_scenario, days_ago]
            [0, 'full',    1],
            [1, 'partial', 2],
            [2, 'none',    3],
            [null, 'full', 1],   // walk-in
            [3, 'full',    4],
            [4, 'partial', 5],
            [0, 'none',    6],
            [null, 'full', 2],   // walk-in
            [5, 'partial', 7],
            [6, 'full',    8],
            [1, 'none',    10],
            [null, 'partial', 3], // walk-in
            [2, 'full',   12],
            [3, 'partial', 14],
            [null, 'none', 5],   // walk-in
        ];

        $walkInNames = ['Ali Bhai', 'Rashid Sahab', 'Begum Ji', 'Farooq Bhai', 'Unknown Customer'];
        $wiIdx = 0;

        foreach ($scenarios as [$custIdx, $payScenario, $daysAgo]) {
            $invoiceItems = $this->randomItems($sizes);
            $subtotal  = collect($invoiceItems)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discount  = rand(0, 1) ? round($subtotal * 0.05) : 0;
            $total     = $subtotal - $discount;

            $data = [
                'invoice_date'    => now()->subDays($daysAgo)->toDateString(),
                'discount_amount' => $discount,
                'tax_amount'      => 0,
                'notes'           => null,
            ];

            if ($custIdx !== null && isset($customers[$custIdx])) {
                $data['customer_id'] = $customers[$custIdx]->id;
                if ($payScenario === 'partial') {
                    $data['due_date'] = now()->addDays(15)->toDateString();
                }
            } else {
                $data['walk_in_name'] = $walkInNames[$wiIdx % count($walkInNames)];
                $wiIdx++;
            }

            $initialPay = match($payScenario) {
                'full'    => $total,
                'partial' => round($total * (rand(3, 7) / 10)),
                default   => 0,
            };

            if ($initialPay > 0) {
                $data['initial_payment'] = $initialPay;
                $data['payment_method']  = 'cash';
            }

            try {
                $service->create($data, $invoiceItems);
            } catch (\Exception $e) {
                $this->command->warn("  Skipped invoice: " . $e->getMessage());
            }
        }

        $this->command->line('  Invoices seeded (' . Invoice::count() . ' total).');
    }

    private function randomItems(object $sizes): array
    {
        $count  = rand(1, 4);
        $picked = $sizes->random(min($count, $sizes->count()));
        $items  = [];

        foreach ($picked as $size) {
            $priceRecord = $size->currentPrice();
            if (!$priceRecord) continue;

            $items[] = [
                'product_size_id' => $size->id,
                'product_name'    => $size->product->name,
                'size_label'      => $size->size_label,
                'quantity'        => rand(1, 3),
                'unit_price'      => $priceRecord->new_price,
            ];
        }

        $fallback = $sizes->first();
        return $items ?: [[
            'product_size_id' => $fallback->id,
            'product_name'    => $fallback->product->name,
            'size_label'      => $fallback->size_label,
            'quantity'        => 1,
            'unit_price'      => $fallback->currentPrice()?->new_price ?? 100,
        ]];
    }
}
