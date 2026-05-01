<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ProductSize;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $data = [
            'todaySales'      => Invoice::whereDate('invoice_date', $today)->sum('total_amount'),
            'todayInvoices'   => Invoice::whereDate('invoice_date', $today)->count(),
            'totalPending'    => Invoice::whereIn('status', ['pending', 'partial'])->sum(\DB::raw('total_amount - paid_amount')),
            'lowStockItems'   => ProductSize::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->with('product')->get(),
            'recentInvoices'  => Invoice::with('customer')->latest()->take(10)->get(),
        ];

        return view('dashboard', $data);
    }
}
