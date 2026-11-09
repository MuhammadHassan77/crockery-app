<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\ProductSize;
use App\Services\PurchaseInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PurchaseInvoiceService $purchaseInvoiceService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseInvoice::with('vendor', 'createdBy')
                ->when($request->status,    fn($q, $v) => $q->where('status', $v))
                ->when($request->date_from, fn($q, $v) => $q->whereDate('invoice_date', '>=', $v))
                ->when($request->date_to,   fn($q, $v) => $q->whereDate('invoice_date', '<=', $v))
                ->when($request->type === 'walkin',  fn($q) => $q->whereNull('vendor_id'))
                ->when($request->type === 'regular', fn($q) => $q->whereNotNull('vendor_id'))
                ->select('purchase_invoices.*');

            return DataTables::of($query)
                ->addColumn('vendor_name', fn($i) => $i->vendor?->name ?? ($i->vendor_name ?: ''))
                ->addColumn('created_by', fn($i) => $i->createdBy->name ?? '')
                ->addColumn('total_amount',  fn($i) => 'PKR ' . number_format($i->total_amount, 0))
                ->addColumn('paid_amount',   fn($i) => 'PKR ' . number_format($i->paid_amount, 0))
                ->addColumn('remaining',     fn($i) => '<span class="' . ($i->remaining_amount > 0 ? 'text-danger fw-semibold' : 'text-muted') . '">PKR ' . number_format($i->remaining_amount, 0) . '</span>')
                ->addColumn('type_badge', fn($i) => $i->vendor_id
                    ? '<span class="badge bg-primary">Regular</span>'
                    : '<span class="badge bg-secondary">Walk-in</span>')
                ->addColumn('status_badge', fn($i) => match ($i->status) {
                    'paid'    => '<span class="badge bg-success">Paid</span>',
                    'partial' => '<span class="badge bg-warning text-dark">Semi-Paid</span>',
                    'pending' => '<span class="badge bg-danger">Pending</span>',
                    default   => '<span class="badge bg-secondary">' . ucfirst($i->status) . '</span>',
                })
                ->addColumn('actions', fn($i) => '
                    ' . ($i->created_at->format('Y-m-d') == now()->format('Y-m-d') ? '<a href="' . route('purchase_invoices.edit', $i) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>' : '') . '
                    <a href="' . route('purchase_invoices.show', $i) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="' . route('purchase_invoices.print', $i) . '" class="btn btn-sm btn-secondary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                    <button class="btn btn-sm btn-primary btn-edit-meta" 
                        data-id="' . $i->id . '" 
                        data-courier="' . e($i->courier_service) . '" 
                        data-built="' . e($i->built_number) . '" 
                        data-received="' . (Carbon::parse($i->received_at)?->format('Y-m-d') ?? '') . '" 
                        title="Edit Metadata"><i class="bi bi-gear"></i></button>
                    <hr class="my-1">
                    <a href="' . route('purchase_invoices.whatsapp', $i) . '" class="btn btn-sm btn-success" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-url="' . route('purchase_invoices.destroy', $i) . '" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->editColumn(
                    'created_at',
                    fn($i) =>
                    $i->created_at?->format('Y-m-d h:i:s A')
                )
                ->editColumn(
                    'updated_at',
                    fn($i) =>
                    $i->updated_at?->format('Y-m-d h:i:s A')
                )
                ->rawColumns(['remaining', 'type_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        return view('purchase_invoices.index');
    }

    public function updateMeta(Request $request, PurchaseInvoice $purchase_invoice)
    {
        try {

            $validator = Validator::make($request->all(), [
                'courier_service' => 'required|string|max:100',
                'built_number' => 'required|string|max:50',
                'received_at' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->toArray()], 200);
            }

            // dd($purchase_invoice);

            $purchase_invoice->courier_service = $request->courier_service ?? '';
            $purchase_invoice->built_number = $request->built_number ?? '';
            $purchase_invoice->received_at = $request->received_at ?? '';

            $purchase_invoice->timestamps = false;

            if (!$purchase_invoice->save()) {
                return response()->json(['success' => false, 'errors' => 'Unable to update'], 200);
            }

            return response()->json(['success' => true, 'message' => 'Updated successfully'], 200);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create invoice. Please try again.');
        }
    }

    public function create()
    {
        $vendors = Customer::active()->orderBy('name')->get();
        $products  = ProductSize::with('product')->active()->get();

        return view('purchase_invoices.create', compact('vendors', 'products'));
    }

    public function store(Request $request)
    {
        // dd(request()->all());
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'vendor_id'               => ['required', 'exists:vendors,id'],
                'walk_in_name'            => ['nullable', 'string', 'max:255'],
                'discount_amount'         => ['nullable', 'numeric', 'min:0'],
                'tax_amount'              => ['nullable', 'numeric', 'min:0'],
                'notes'                   => ['nullable', 'string'],
                'invoice_date'            => ['nullable', 'date'],
                'due_date'                => ['nullable', 'date'],
                'courier_service'         => ['nullable', 'string'],
                'built_number'            => ['nullable', 'string'],
                'received_at'             => ['nullable', 'date'],
                'initial_payment'         => ['nullable', 'numeric', 'min:0'],
                'payment_method'          => ['nullable', 'string'],
                'items'                   => ['required', 'array', 'min:1'],
                'items.*.product_size_id' => ['required', 'exists:product_sizes,id'],
                'items.*.quantity'        => ['required', 'integer', 'min:1'],
                'items.*.weight'          => ['nullable', 'numeric', 'min:0'],
                'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
                'items.*.product_name'    => ['required', 'string'],
                'items.*.product_name_ur' => ['nullable', 'string'],
                'items.*.size_label'      => ['required', 'string'],
                'items.*.size_label_ur'   => ['nullable', 'string'],
            ]);
            // dd(request()->all(), $validated);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->toArray()], 422);
            }
            $invoice = $this->purchaseInvoiceService->create($data, $data['items']);

            return response()->json(['success' => true, 'redirection_url' => route('purchase_invoices.show', $invoice),  'message' => "Purchase Invoice {$invoice->invoice_number} created."]);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create invoice. Please try again.');
        }
    }

    public function show(PurchaseInvoice $purchase_invoice)
    {
        $purchase_invoice->load(['items', 'purchase_payments', 'vendor']);
        $previousBalance = $this->getPreviousBalance($purchase_invoice);

        return view('purchase_invoices.show', compact('purchase_invoice', 'previousBalance'));
    }

    public function print(PurchaseInvoice $invoice)
    {
        $invoice->load(['items', 'purchase_payments', 'vendor']);
        $previousBalance = $this->getPreviousBalance($invoice);
        return view('purchase_invoices.print', compact('invoice', 'previousBalance'));
    }

    public function pdf(PurchaseInvoice $invoice)
    {
        $invoice->load(['items', 'purchase_payments', 'vendor']);
        $previousBalance = $this->getPreviousBalance($invoice);
        $pdf = Pdf::loadView('purchase_invoices.print', compact('invoice', 'previousBalance'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * Get the vendor's outstanding balance from other invoices (before this one).
     */
    private function getPreviousBalance(PurchaseInvoice $purchase_invoice): float
    {
        if (! $purchase_invoice->vendor_id) {
            return 0;
        }

        return (float) PurchaseInvoice::where('vendor_id', $purchase_invoice->vendor_id)
            ->where('id', '!=', $purchase_invoice->id)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as balance')
            ->value('balance') ?? 0;
    }

    public function whatsapp(PurchaseInvoice $purchase_invoice)
    {
        $shareUrl = route('purchase_invoice.public', $purchase_invoice->share_token);
        $message  = urlencode("Hello! Your invoice *{$purchase_invoice->invoice_number}* is ready.\nTotal: PKR {$purchase_invoice->total_amount}\nView: {$shareUrl}");

        return redirect("https://wa.me/?text={$message}");
    }

    public function publicView(string $token)
    {
        $invoice = PurchaseInvoice::where('share_token', $token)
            ->with(['items', 'purchase_payments', 'vendor'])
            ->firstOrFail();
        $previousBalance = $this->getPreviousBalance($invoice);

        return view('purchase_invoices.print', compact('invoice', 'previousBalance'));
    }

    public function destroy(PurchaseInvoice $purchase_invoice)
    {
        try {
            DB::transaction(function () use ($purchase_invoice) {
                $purchase_invoice->delete();
            });

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('purchase_invoices.index')->with('success', 'Invoice deleted.');
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete invoice. Please try again.', null, 422);
        }
    }

    public function edit(PurchaseInvoice $purchase_invoice)
    {
        $vendors = Customer::active()->orderBy('name')->get();
        $products  = ProductSize::with('product')->active()->get();
        $purchase_invoice->load(['items', 'purchase_payments', 'vendor']);
        return view('purchase_invoices.edit', compact('purchase_invoice', 'vendors', 'products'));
    }

    public function update(Request $request, PurchaseInvoice $purchase_invoice)
    {
        try {

            // dd(request()->all());
            $data = $request->all();
            $validator = Validator::make($data, [
                'vendor_id'               => ['required', 'exists:vendors,id'],
                'discount_amount'         => ['nullable', 'numeric', 'min:0'],
                'tax_amount'              => ['nullable', 'numeric', 'min:0'],
                'notes'                   => ['nullable', 'string'],
                'invoice_date'            => ['nullable', 'date'],
                'due_date'                => ['nullable', 'date'],
                'courier_service'         => ['nullable', 'string'],
                'built_number'            => ['nullable', 'string'],
                'received_at'             => ['nullable', 'date'],
                'initial_payment'         => ['nullable', 'numeric', 'min:0'],
                'payment_method'          => ['nullable', 'string'],
                'update_prices'           => ['nullable', 'boolean'],
                'items'                   => ['required', 'array', 'min:1'],
                'items.*.product_size_id' => ['required', 'exists:product_sizes,id'],
                'items.*.quantity'        => ['required', 'integer', 'min:1'],
                'items.*.weight'          => ['nullable', 'numeric', 'min:0'],
                'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
                'items.*.product_name'    => ['required', 'string'],
                'items.*.product_name_ur' => ['nullable', 'string'],
                'items.*.size_label'      => ['required', 'string'],
                'items.*.size_label_ur'   => ['nullable', 'string'],
            ]);
            // dd(request()->all(), $validated);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->toArray()], 422);
            }
            $paidBefore = $purchase_invoice->purchase_payments()->sum('amount');

            try {
                $updated = $this->purchaseInvoiceService->updatePurchaseInvoice($purchase_invoice, $data, $data['items'], !empty($data['update_prices']));
            } catch (\Throwable $e) {
                return $this->handleException($request, $e, 'Failed to update invoice');
            }

            $paidAfter = $paidBefore;
            $warning = null;
            if ($paidAfter > $updated->total_amount) {
                $warning = 'Existing payments exceed the new total; invoice marked paid and no refunds were processed.';
            }

            $resp = response()->json(['success' => true, 'redirection_url' => route('purchase_invoices.show', $updated),  'message' => "Invoice {$updated->invoice_number} updated."], 200);
            if ($warning) return response()->json(['success' => false, 'message' => $warning], 500);
            return $resp;
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create invoice. Please try again.');
        }
    }
}
