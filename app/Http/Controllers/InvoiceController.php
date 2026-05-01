<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProductSize;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Invoice::with('customer')
                ->when($request->status,    fn($q, $v) => $q->where('status', $v))
                ->when($request->date_from, fn($q, $v) => $q->whereDate('invoice_date', '>=', $v))
                ->when($request->date_to,   fn($q, $v) => $q->whereDate('invoice_date', '<=', $v))
                ->when($request->type === 'walkin',  fn($q) => $q->whereNull('customer_id'))
                ->when($request->type === 'regular', fn($q) => $q->whereNotNull('customer_id'))
                ->select('invoices.*');

            return DataTables::of($query)
                ->addColumn('customer_name', fn($i) => $i->customer?->name ?? ($i->customer_name ?: 'Walk-in'))
                ->addColumn('total_amount',  fn($i) => 'PKR ' . number_format($i->total_amount, 0))
                ->addColumn('paid_amount',   fn($i) => 'PKR ' . number_format($i->paid_amount, 0))
                ->addColumn('remaining',     fn($i) => '<span class="' . ($i->remaining_amount > 0 ? 'text-danger fw-semibold' : 'text-muted') . '">PKR ' . number_format($i->remaining_amount, 0) . '</span>')
                ->addColumn('type_badge', fn($i) => $i->customer_id
                    ? '<span class="badge bg-primary">Regular</span>'
                    : '<span class="badge bg-secondary">Walk-in</span>')
                ->addColumn('status_badge', fn($i) => match($i->status) {
                    'paid'    => '<span class="badge bg-success">Paid</span>',
                    'partial' => '<span class="badge bg-warning text-dark">Semi-Paid</span>',
                    'pending' => '<span class="badge bg-danger">Pending</span>',
                    default   => '<span class="badge bg-secondary">' . ucfirst($i->status) . '</span>',
                })
                ->addColumn('actions', fn($i) => '
                    <a href="' . route('invoices.show', $i) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="' . route('invoices.print', $i) . '" class="btn btn-sm btn-secondary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                    <a href="' . route('invoices.whatsapp', $i) . '" class="btn btn-sm btn-success" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-url="' . route('invoices.destroy', $i) . '" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->rawColumns(['remaining', 'type_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        return view('invoices.index');
    }

    public function create()
    {
        $customers = Customer::active()->orderBy('name')->get();
        $products  = ProductSize::with('product')->active()->get();

        return view('invoices.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'             => ['nullable', 'exists:customers,id'],
            'walk_in_name'            => ['nullable', 'string', 'max:255'],
            'discount_amount'         => ['nullable', 'numeric', 'min:0'],
            'tax_amount'              => ['nullable', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string'],
            'invoice_date'            => ['nullable', 'date'],
            'due_date'                => ['nullable', 'date'],
            'initial_payment'         => ['nullable', 'numeric', 'min:0'],
            'payment_method'          => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_size_id' => ['required', 'exists:product_sizes,id'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.product_name'    => ['required', 'string'],
            'items.*.product_name_ur' => ['nullable', 'string'],
            'items.*.size_label'      => ['required', 'string'],
            'items.*.size_label_ur'   => ['nullable', 'string'],
        ]);

        $invoice = $this->invoiceService->create($validated, $validated['items']);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['items', 'payments', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        return view('invoices.show', compact('invoice', 'previousBalance'));
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['items', 'payments', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        return view('invoices.print', compact('invoice', 'previousBalance'));
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['items', 'payments', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        $pdf = Pdf::loadView('invoices.print', compact('invoice', 'previousBalance'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * Get the customer's outstanding balance from other invoices (before this one).
     */
    private function getPreviousBalance(Invoice $invoice): float
    {
        if (! $invoice->customer_id) {
            return 0;
        }

        return (float) Invoice::where('customer_id', $invoice->customer_id)
            ->where('id', '!=', $invoice->id)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as balance')
            ->value('balance') ?? 0;
    }

    public function whatsapp(Invoice $invoice)
    {
        $shareUrl = route('invoice.public', $invoice->share_token);
        $message  = urlencode("Hello! Your invoice *{$invoice->invoice_number}* is ready.\nTotal: PKR {$invoice->total_amount}\nView: {$shareUrl}");

        return redirect("https://wa.me/?text={$message}");
    }

    public function publicView(string $token)
    {
        $invoice = Invoice::where('share_token', $token)
            ->with(['items', 'payments', 'customer'])
            ->firstOrFail();

        return view('invoices.public', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function edit(Invoice $invoice)
    {
        abort(403, 'Invoices cannot be edited after creation.');
    }

    public function update() {}
}
