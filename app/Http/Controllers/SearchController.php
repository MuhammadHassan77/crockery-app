<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Customers
        Customer::where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(4)
            ->get()
            ->each(function ($c) use (&$results) {
                $results[] = [
                    'type'     => 'customer',
                    'icon'     => 'bi-person',
                    'label'    => $c->name,
                    'sublabel' => $c->phone ?? $c->city ?? 'Customer',
                    'url'      => route('customers.show', $c),
                ];
            });

        // Vendors
        Vendor::where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(4)
            ->get()
            ->each(function ($c) use (&$results) {
                $results[] = [
                    'type'     => 'vendor',
                    'icon'     => 'bi-person',
                    'label'    => $c->name,
                    'sublabel' => $c->phone ?? $c->city ?? 'vendor',
                    'url'      => route('vendors.show', $c),
                ];
            });

        // Invoices
        Invoice::with('customer')
            ->where(function ($q2) use ($q) {
                $q2->where('invoice_number', 'like', "%{$q}%")
                   ->orWhere('walk_in_name', 'like', "%{$q}%")
                   ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$q}%"));
            })
            ->latest()
            ->limit(4)
            ->get()
            ->each(function ($i) use (&$results) {
                $badge = match($i->status) {
                    'paid'    => 'success',
                    'partial' => 'warning',
                    default   => 'danger',
                };
                $results[] = [
                    'type'     => 'invoice',
                    'icon'     => 'bi-receipt',
                    'label'    => $i->invoice_number,
                    'sublabel' => ($i->customer?->name ?? $i->walk_in_name ?? 'Walk-in') . ' · PKR ' . number_format($i->total_amount, 0),
                    'badge'    => ['text' => ucfirst($i->status), 'color' => $badge],
                    'url'      => route('invoices.show', $i),
                ];
            });

        // Purchase Invoices
        PurchaseInvoice::with('vendor')
            ->where(function ($q2) use ($q) {
                $q2->where('invoice_number', 'like', "%{$q}%")
                   ->orWhereHas('vendor', fn($cq) => $cq->where('name', 'like', "%{$q}%"));
            })
            ->latest()
            ->limit(4)
            ->get()
            ->each(function ($i) use (&$results) {
                $badge = match($i->status) {
                    'paid'    => 'success',
                    'partial' => 'warning',
                    default   => 'danger',
                };
                $results[] = [
                    'type'     => 'purchase_invoice',
                    'icon'     => 'bi-receipt',
                    'label'    => $i->invoice_number,
                    'sublabel' => ($i->vendor?->name ?? $i->walk_in_name ?? 'Walk-in') . ' · PKR ' . number_format($i->total_amount, 0),
                    'badge'    => ['text' => ucfirst($i->status), 'color' => $badge],
                    'url'      => route('purchase_invoices.show', $i),
                ];
            });

        // Products
        Product::with('category')
            ->where('name', 'like', "%{$q}%")
            ->limit(3)
            ->get()
            ->each(function ($p) use (&$results) {
                $results[] = [
                    'type'     => 'product',
                    'icon'     => 'bi-box-seam',
                    'label'    => $p->name,
                    'sublabel' => $p->category?->name ?? 'Product',
                    'url'      => route('products.show', $p),
                ];
            });

        return response()->json(['results' => $results, 'query' => $q]);
    }
}
