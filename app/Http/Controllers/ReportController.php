<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProductSize;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $invoices = Invoice::with('customer')
            ->whereBetween('invoice_date', [$from, $to])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest('invoice_date')
            ->get();

        $summary = [
            'total_sales'   => $invoices->sum('total_amount'),
            'total_paid'    => $invoices->sum('paid_amount'),
            'total_pending' => $invoices->sum(fn($i) => $i->total_amount - $i->paid_amount),
            'count'         => $invoices->count(),
        ];

        return view('reports.sales', compact('invoices', 'summary', 'from', 'to'));
    }

    public function payments(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $payments = Payment::with('invoice.customer')
            ->whereBetween('paid_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->latest('paid_at')
            ->get();

        return view('reports.payments', compact('payments', 'from', 'to'));
    }

    public function stock()
    {
        $sizes = ProductSize::with('product.category')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();

        $lowStock = $sizes->filter(fn($s) => $s->isLowStock());

        return view('reports.stock', compact('sizes', 'lowStock'));
    }
}
