<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StockMovement;
use App\Models\Customer;
use App\Services\CustomerAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoricalGiftInvoices extends Command
{
    protected $signature = 'import:gift-invoices {--dry-run}';

    protected $description = 'Import historical Amber gift invoices';

    public function handle(CustomerAccountService $accountService): int
    {
        $invoiceData = [
            [
                'invoice_number' => 'INV-2026-00001',
                'customer_id' => 1,
                'amount' => 382350.00,
            ],
            [
                'invoice_number' => 'INV-2026-00055',
                'customer_id' => 2,
                'amount' => 154960.00,
            ],
            [
                'invoice_number' => 'INV-2026-00057',
                'customer_id' => 4,
                'amount' => 147302.00,
            ],

            // TODO:
            // باقی تمام 100 rows یہاں add کریں۔
        ];

        if ($this->option('dry-run')) {
            $this->info('Dry run mode enabled.');
            $this->table(
                ['Invoice', 'Customer', 'Amount'],
                collect($invoiceData)
                    ->map(fn($row) => [
                        $row['invoice_number'],
                        $row['customer_id'],
                        $row['amount'],
                    ])
                    ->toArray()
            );

            return self::SUCCESS;
        }

        DB::transaction(function () use ($invoiceData, $accountService) {

            foreach ($invoiceData as $row) {

                if (
                    Invoice::where(
                        'invoice_number',
                        $row['invoice_number']
                    )->exists()
                ) {
                    $this->warn(
                        "Skipping {$row['invoice_number']} (already exists)"
                    );

                    continue;
                }

                $invoice = Invoice::create([
                    'invoice_number' => $row['invoice_number'],
                    'customer_id' => $row['customer_id'],
                    'subtotal' => $row['amount'],
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $row['amount'],
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'invoice_date' => '2026-07-01',
                    'created_by' => 1,
                    'created_at' => '2026-07-01 00:00:00',
                    'updated_at' => '2026-07-01 00:00:00',
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_size_id' => 1389,
                    'product_name' => 'گفٹ سیٹ Amber',
                    'product_name_ur' => 'گفٹ سیٹ Amber',
                    'size_label' => 'گفٹ سیٹ Amber ۔۔۔',
                    'size_label_ur' => null,
                    'quantity' => 1,
                    'unit_price' => $row['amount'],
                    'weight' => null,
                    'total_price' => $row['amount'],
                    'created_at' => '2026-07-01 00:00:00',
                    'updated_at' => '2026-07-01 00:00:00',
                ]);

                StockMovement::create([
                    'product_size_id' => 1389,
                    'type' => 'sale',
                    'quantity_change' => -1,
                    'stock_after' => 0,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'purchase_price' => null,
                    'created_by' => 1,
                    'created_at' => '2026-07-01 00:00:00',
                    'updated_at' => '2026-07-01 00:00:00',
                ]);

                $customer = Customer::find($invoice->customer_id);
                if ($customer) {
                    $accountService->reconcile($customer);
                }

                $this->info(
                    "Imported {$invoice->invoice_number}"
                );
            }
        });

        $this->info('Import completed successfully.');

        return self::SUCCESS;
    }
}
