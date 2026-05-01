# 🏺 Crockery Shop — Billing & Inventory Management System

A complete web-based billing and inventory management system built with Laravel, designed for crockery (bartan) shop owners to manage products, customers, invoicing, and payments — with full Urdu/English support.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Folder Structure](#folder-structure)
- [Database Schema](#database-schema)
- [Core Modules](#core-modules)
- [Invoice Flow](#invoice-flow)
- [Payment Handling](#payment-handling)
- [Stock Management](#stock-management)
- [Price History](#price-history)
- [WhatsApp Sharing](#whatsapp-sharing)
- [Reports & Exports](#reports--exports)
- [Backup System](#backup-system)
- [Print System](#print-system)
- [User Roles](#user-roles)
- [Activity Logs](#activity-logs)
- [Packages Used](#packages-used)
- [Development Plan](#development-plan)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### Core Features
- **Authentication** — Secure login with two roles: Admin and User (`is_admin` flag)
- **Product Catalog** — Products with multiple sizes, each size having its own price
- **Stock Management** — Real-time stock tracking per product/size with low-stock alerts
- **Price History** — Complete price change history with effective date ranges
- **Customer Management** — Full customer CRUD with walk-in customer support
- **Billing & Invoicing** — Auto-numbered invoices, line items, discounts
- **Payment Tracking** — Partial payments (udhar), payment history per invoice
- **Dashboard** — Daily sales summary, revenue, pending payments, low-stock overview
- **Reports** — Sales and payment reports with date-wise filtering
- **Print System** — Printable invoices in both English and Urdu
- **WhatsApp Sharing** — Share invoices via WhatsApp with a signed public link
- **Search & Filters** — Global search across products, customers, invoices
- **Backup System** — Download full database backup on demand
- **Activity Logs** — Complete audit trail of all user actions
- **Dark & Light Mode** — UI supports both themes
- **Responsive Design** — Works on mobile and desktop

### Invoice Status Types
| Status | Description |
|--------|-------------|
| `paid` | Full payment received |
| `partial` | Partial payment received (udhar/credit) |
| `pending` | No payment received yet |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11.x |
| Frontend | Blade + Bootstrap 5 |
| Admin Template | NiceAdmin (BootstrapMade) |
| Database | MySQL 8.x |
| Tables | Yajra DataTables (server-side) |
| PDF | barryvdh/laravel-dompdf |
| Excel Export | Maatwebsite/Laravel-Excel |
| Backup | spatie/laravel-backup |
| Image Processing | intervention/image |
| Authentication | Laravel Breeze (customized) |

---

## System Requirements

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL >= 8.0
- Laravel >= 11.x
- Web Server: Apache / Nginx (or `php artisan serve` for local)

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/crockery-shop.git
cd crockery-shop
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
npm run build
```

### 4. Create Environment File

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure the Database

Open `.env` and update your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crockery_shop
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 6. Run Migrations & Seeders

```bash
php artisan migrate --seed
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

---

## Configuration

### Environment Variables

```env
# App
APP_NAME="Crockery Shop"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crockery_shop
DB_USERNAME=root
DB_PASSWORD=

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null

# Backup Disk
BACKUP_DISK=local

# Low Stock Default Threshold
LOW_STOCK_THRESHOLD=5

# Invoice Number Prefix
INVOICE_PREFIX=INV
```

### Default Admin Credentials (after seeding)

```
Email:    admin@crockeryshop.com
Password: password
```

> **Important:** Change these immediately after first login.

---

## Database Setup

### Create the Database

```sql
CREATE DATABASE crockery_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Run Migrations

```bash
php artisan migrate
```

### Seed Sample Data

```bash
php artisan db:seed
```

This creates:
- 1 Admin user
- 1 Staff user
- 10 sample products with sizes and prices
- 5 sample customers
- 10 sample invoices with payments

---

## Folder Structure

```
crockery-shop/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProductSizeController.php
│   │   │   │   ├── ProductPriceController.php
│   │   │   │   ├── StockController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── BackupController.php
│   │   │   │   └── ActivityLogController.php
│   │   │   └── User/
│   │   │       ├── DashboardController.php
│   │   │       ├── InvoiceController.php
│   │   │       └── PaymentController.php
│   │   └── Middleware/
│   │       ├── IsAdmin.php
│   │       └── ActivityLogger.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Product.php
│   │   ├── ProductSize.php
│   │   ├── ProductPrice.php
│   │   ├── Stock.php
│   │   ├── Invoice.php
│   │   ├── InvoiceItem.php
│   │   ├── Payment.php
│   │   ├── StockMovement.php
│   │   └── ActivityLog.php
│   └── Services/
│       ├── InvoiceService.php
│       ├── PaymentService.php
│       ├── StockService.php
│       └── ReportService.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_customers_table.php
│   │   ├── create_products_table.php
│   │   ├── create_product_sizes_table.php
│   │   ├── create_product_prices_table.php
│   │   ├── create_stock_table.php
│   │   ├── create_invoices_table.php
│   │   ├── create_invoice_items_table.php
│   │   ├── create_payments_table.php
│   │   ├── create_stock_movements_table.php
│   │   └── create_activity_logs_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       ├── ProductSeeder.php
│       └── CustomerSeeder.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php
│       │   └── print.blade.php
│       ├── auth/
│       │   └── login.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── products/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── sizes.blade.php
│       ├── customers/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       ├── invoices/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── show.blade.php
│       │   ├── print.blade.php
│       │   └── public.blade.php
│       ├── payments/
│       │   └── index.blade.php
│       └── reports/
│           ├── sales.blade.php
│           └── payments.blade.php
└── routes/
    └── web.php
```

---

## Database Schema

### Tables Overview

| Table | Description |
|-------|-------------|
| `users` | System users (admin and staff) |
| `customers` | Shop customers (nullable for walk-in) |
| `products` | Product catalog |
| `product_sizes` | Size variants per product |
| `product_prices` | Price history per size |
| `stock` | Current stock level per size |
| `invoices` | Invoice header records |
| `invoice_items` | Line items on each invoice |
| `payments` | Payment events per invoice |
| `stock_movements` | Full stock audit trail |
| `activity_logs` | User action audit trail |

### Key Relationships

```
Product ──< ProductSize ──< ProductPrice (history)
                       ──── Stock (1:1)
                       ──< InvoiceItem >── Invoice ──< Payment
                                                  >── Customer
```

### Invoice Status Flow

```
[Created] ──► pending
                │
                ▼
         partial payment ──► partial
                │
                ▼
         full payment ──► paid
```

---

## Core Modules

### Product Management

- Add products with name, category, description, and image
- Add multiple size variants per product (e.g. 250ml, 500ml, 1L, or "Standard")
- Each size has its own price and stock level
- Soft delete products (archive without data loss)

### Stock Management

- Stock is tracked per `product_size`, not just per product
- All stock changes go through `StockMovement` records first
- `StockService` then updates the `stock.quantity` counter
- Low stock alerts appear on the dashboard when `quantity <= low_stock_threshold`
- Manual stock adjustments are logged with reason notes

### Customer Management

- Full customer CRUD with name, phone, and address
- Walk-in sales do not require a customer record (`customer_id` is nullable on invoices)
- Customer ledger shows all invoices and outstanding balance

---

## Invoice Flow

### Creating an Invoice

1. Select customer (or leave blank for walk-in)
2. Add line items — select product, size, quantity (price auto-filled from current price)
3. Optionally apply a discount (fixed amount or percentage)
4. Submit — `InvoiceService` handles the rest:

```
InvoiceService::create()
    ├── Generate unique invoice number (INV-YYYY-NNNN)
    ├── Snapshot unit_price from current ProductPrice
    ├── Calculate subtotal, discount, total_amount
    ├── Set paid_amount = 0, balance_due = total_amount
    ├── Set status = 'pending'
    ├── Save Invoice + InvoiceItems
    └── StockService::deduct() for each line item
```

### Invoice Number Generation

Format: `INV-2024-0001`

Generated atomically inside a database transaction:

```php
// InvoiceService.php
$year    = now()->year;
$last    = Invoice::whereYear('created_at', $year)->lockForUpdate()->max('id') ?? 0;
$number  = 'INV-' . $year . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
```

---

## Payment Handling

### Recording a Payment

Handled by `PaymentService::record()`:

```
PaymentService::record($invoice, $amount, $method)
    ├── Validate amount <= balance_due
    ├── Insert into payments table
    ├── Recalculate invoice.paid_amount = SUM(payments.amount)
    ├── Recalculate invoice.balance_due = total_amount - paid_amount
    └── Update invoice.status:
            paid_amount == 0               → 'pending'
            paid_amount >= total_amount    → 'paid'
            otherwise                      → 'partial'
```

### Payment Methods Supported

- Cash
- Bank Transfer
- Cheque
- Online (EasyPaisa, JazzCash, etc.)
- Other

### Partial Payment Example (Udhar)

```
Invoice Total:   Rs. 5,000
Payment 1:       Rs. 2,000  → status = partial, balance = 3,000
Payment 2:       Rs. 1,500  → status = partial, balance = 1,500
Payment 3:       Rs. 1,500  → status = paid,    balance = 0
```

---

## Stock Management

### Stock Deduction on Sale

```php
// StockService.php
public function deduct(ProductSize $size, int $qty, int $invoiceId): void
{
    DB::transaction(function () use ($size, $qty, $invoiceId) {
        $stock = Stock::where('product_size_id', $size->id)->lockForUpdate()->first();
        
        if ($stock->quantity < $qty) {
            throw new InsufficientStockException();
        }

        $stock->decrement('quantity', $qty);

        StockMovement::create([
            'product_size_id' => $size->id,
            'type'            => 'out',
            'quantity'        => $qty,
            'reference_id'    => $invoiceId,
            'reference_type'  => 'invoice',
            'created_by'      => auth()->id(),
        ]);

        if ($stock->quantity <= $stock->low_stock_threshold) {
            // Flag for dashboard alert
        }
    });
}
```

### Movement Types

| Type | Triggered By |
|------|-------------|
| `in` | Manual stock addition |
| `out` | Invoice sale |
| `adjustment` | Manual correction |
| `return` | Customer return |

---

## Price History

Prices are **never deleted** — only closed out:

```php
// When setting a new price:
DB::transaction(function () use ($sizeId, $newPrice, $costPrice) {
    // Close the current active price
    ProductPrice::where('product_size_id', $sizeId)
        ->whereNull('effective_to')
        ->update(['effective_to' => now()]);

    // Insert the new price
    ProductPrice::create([
        'product_size_id' => $sizeId,
        'price'           => $newPrice,
        'cost_price'      => $costPrice,
        'effective_from'  => now(),
        'effective_to'    => null,   // null = currently active
        'created_by'      => auth()->id(),
    ]);
});
```

### Getting the Current Price

```php
// ProductSize model scope
public function currentPrice(): HasOne
{
    return $this->hasOne(ProductPrice::class)->whereNull('effective_to')->latest();
}
```

> Invoice items store `unit_price` as a snapshot at the time of sale — they never re-query price history.

---

## WhatsApp Sharing

No third-party API required. Uses Laravel signed URLs + the `wa.me` deep link:

### Step 1 — Generate a Signed Public URL

```php
// InvoiceController.php
$publicUrl = URL::signedRoute('invoice.public', ['invoice' => $invoice->id]);
```

### Step 2 — Build the WhatsApp Link

```php
$message = "Invoice {$invoice->invoice_number}\n"
         . "Amount: Rs. {$invoice->total_amount}\n"
         . "View: {$publicUrl}";

$whatsappUrl = 'https://wa.me/?text=' . urlencode($message);
```

### Step 3 — Public Invoice View

```php
// routes/web.php
Route::get('/invoice/{invoice}/view', [InvoiceController::class, 'publicView'])
     ->name('invoice.public')
     ->middleware('signed');
```

The public view shows a read-only, print-friendly invoice — no login required.

---

## Reports & Exports

### Sales Report

- Filter by date range, customer, or product
- Totals: gross sales, discounts, net revenue
- Export to Excel or PDF

### Payment Report

- Filter by date, payment method, or status
- Shows paid, partial, and pending invoices
- Outstanding balance summary per customer

### Dashboard Widgets

| Widget | Data |
|--------|------|
| Today's Sales | SUM of invoices created today |
| Total Revenue | SUM of all payments received |
| Pending Payments | SUM of all balance_due |
| Low Stock Items | Products below threshold |
| Recent Invoices | Last 10 invoices |

---

## Backup System

Uses `spatie/laravel-backup` for scheduled backups, plus an on-demand download:

### On-Demand Download (Admin Only)

```php
// BackupController.php
public function download(): StreamedResponse
{
    $filename = 'backup-' . now()->format('Y-m-d-His') . '.sql';
    
    return response()->streamDownload(function () {
        $db = config('database.connections.mysql');
        passthru("mysqldump -u{$db['username']} -p{$db['password']} {$db['database']}");
    }, $filename);
}
```

### Scheduled Backup

Add to `app/Console/Kernel.php`:

```php
$schedule->command('backup:run')->daily()->at('02:00');
```

---

## Print System

### Invoice Print View

- Clean print-only Blade layout (`layouts/print.blade.php`)
- Hides all navigation and action buttons
- Supports both English and Urdu shop name/address header
- Triggered via `window.print()` on a dedicated print route

### Urdu Support

Add the Urdu font in your print layout:

```html
<style>
  @font-face {
    font-family: 'NafeesWeb';
    src: url('/fonts/NafeesWeb.woff2') format('woff2');
  }
  .urdu { font-family: 'NafeesWeb', serif; direction: rtl; text-align: right; }
</style>
```

---

## User Roles

| Feature | Admin | User |
|---------|-------|------|
| Dashboard | ✅ | ✅ |
| Create Invoice | ✅ | ✅ |
| Record Payment | ✅ | ✅ |
| Manage Products | ✅ | ❌ |
| Manage Stock | ✅ | ❌ |
| Manage Customers | ✅ | ✅ |
| View Reports | ✅ | ❌ |
| Download Backup | ✅ | ❌ |
| Manage Users | ✅ | ❌ |
| View Activity Logs | ✅ | ❌ |

Roles are enforced via the `IsAdmin` middleware applied to admin route groups.

---

## Activity Logs

All key actions are automatically logged via the `ActivityLogger` middleware and model observers:

| Action | Logged |
|--------|--------|
| Login / Logout | ✅ |
| Invoice created | ✅ |
| Payment recorded | ✅ |
| Product created/updated/deleted | ✅ |
| Price changed | ✅ |
| Stock adjusted | ✅ |
| Customer created/updated/deleted | ✅ |
| Backup downloaded | ✅ |

Log entry example:

```json
{
  "user_id": 1,
  "action": "created_invoice",
  "model_type": "Invoice",
  "model_id": 42,
  "new_values": { "invoice_number": "INV-2024-0042", "total_amount": 5000 },
  "ip_address": "192.168.1.1"
}
```

---

## Packages Used

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "yajra/laravel-datatables-oracle": "^10.0",
    "barryvdh/laravel-dompdf": "^2.0",
    "maatwebsite/excel": "^3.1",
    "spatie/laravel-backup": "^8.0",
    "intervention/image": "^3.0"
  }
}
```

Install all packages:

```bash
composer require yajra/laravel-datatables-oracle barryvdh/laravel-dompdf maatwebsite/excel spatie/laravel-backup intervention/image
```

---

## Development Plan

### Phase 1 — Foundation (Week 1)
- [ ] Laravel project setup
- [ ] Database migrations (all 11 tables)
- [ ] Eloquent models with relationships
- [ ] Authentication (login/logout)
- [ ] NiceAdmin template integration
- [ ] Basic layout and navigation

### Phase 2 — Products & Stock (Week 2)
- [ ] Product CRUD with image upload
- [ ] Product sizes management
- [ ] Price setting with history
- [ ] Stock management with movements
- [ ] Low stock alerts on dashboard

### Phase 3 — Customers & Invoicing (Week 3)
- [ ] Customer CRUD
- [ ] Invoice creation with line items
- [ ] Invoice number generation
- [ ] Stock deduction on invoice save
- [ ] Invoice list with DataTables
- [ ] Invoice detail/show view

### Phase 4 — Payments (Week 4)
- [ ] Record payments against invoices
- [ ] Payment history per invoice
- [ ] Invoice status auto-update
- [ ] Customer ledger (outstanding balances)
- [ ] Partial payment (udhar) tracking

### Phase 5 — Reports & Dashboard (Week 5)
- [ ] Dashboard widgets (sales, revenue, pending, low stock)
- [ ] Sales report with date filters
- [ ] Payment report
- [ ] Export to Excel
- [ ] Export to PDF

### Phase 6 — Print, WhatsApp & Backup (Week 6)
- [ ] Printable invoice view (English + Urdu)
- [ ] PDF invoice generation
- [ ] WhatsApp sharing with signed URL
- [ ] Database backup download
- [ ] Scheduled automated backup

### Phase 7 — Polish & Deployment (Week 7)
- [ ] Activity logs viewer
- [ ] Dark/light mode toggle
- [ ] Global search
- [ ] Advanced filters
- [ ] Performance optimization (indexes, eager loading)
- [ ] Production deployment setup

---

## Screenshots

> Screenshots will be added after UI implementation.

| Screen | Description |
|--------|-------------|
| Dashboard | Daily summary, alerts, recent invoices |
| Invoice Create | Line item builder with live totals |
| Invoice Print | Clean print-ready layout |
| Customer Ledger | All invoices and balance per customer |
| Stock Management | Current levels with movement history |
| Reports | Sales and payment charts with filters |

---

## Recommended VS Code Extensions

- **Laravel Blade Snippets** — Blade syntax highlighting
- **Laravel Artisan** — Run artisan commands from VS Code
- **PHP Intelephense** — PHP IntelliSense
- **MySQL** — Database browser inside VS Code
- **DotENV** — `.env` file syntax highlighting

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m 'Add your feature'`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding style
- Use Laravel conventions (resource controllers, form requests, service classes)
- Write migrations for every schema change
- Never put business logic in controllers — use Service classes
- All stock changes must go through `StockService`
- All payment calculations must go through `PaymentService`

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Support

For issues or feature requests, open a GitHub issue or contact the developer.

> Built with ❤️ for small shop owners in Pakistan — with full Urdu language support.
