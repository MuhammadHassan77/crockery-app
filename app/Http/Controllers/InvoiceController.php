<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPaymentAllocation;
use App\Models\Invoice;
use App\Models\PaymentHistory;
use App\Models\ProductSize;
use App\Services\CustomerAccountService;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private CustomerAccountService $customerAccountService,
    ) {}

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
                ->addColumn('created_by', fn($i) => $i->createdBy->name ?? '')
                ->addColumn('total_amount',  fn($i) => $i->cash_refund_amount > $i->total_amount ? 'PKR -' . number_format($i->cash_refund_amount, 0) : 'PKR ' . number_format($i->total_amount, 0))
                // ->addColumn('cash_refund_amount',  fn($i) => $i->cash_refund_amount != 0 ? 'PKR -' . number_format($i->cash_refund_amount, 0) : '-')
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
                    '. ($i->created_at->format('Y-m-d') == now()->format('Y-m-d') ? '<a href="' . route('invoices.edit', $i) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>' : '').'
                    <a href="' . route('invoices.show', $i) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="' . route('invoices.print', $i) . '" class="btn btn-sm btn-secondary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                    <hr class="my-1">
                    <a href="' . route('invoices.whatsapp', $i) . '" class="btn btn-sm btn-success" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-url="' . route('invoices.destroy', $i) . '" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->editColumn('created_at', fn ($i) =>
                    $i->created_at?->format('Y-m-d h:i:s A')
                )
                ->editColumn('updated_at', fn ($i) =>
                    $i->updated_at?->format('Y-m-d h:i:s A')
                )
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
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'customer_id'             => ['nullable', 'exists:customers,id'],
                'walk_in_name'            => ['nullable', 'string', 'max:255'],
                'walk_in_phone'           => ['nullable', 'string', 'max:15'],
                'discount_amount'         => ['nullable', 'numeric', 'min:0'],
                'tax_amount'              => ['nullable', 'numeric', 'min:0'],
                'nug'                     => ['required', 'integer', 'min:0'],
                'nug_amount'              => ['required', 'numeric', 'min:0'],
                'return_item_amount'      => ['required', 'numeric', 'min:0'],
                'exchange_item_amount'    => ['required', 'numeric', 'min:0'],
                'notes'                   => ['nullable', 'string'],
                'invoice_date'            => ['nullable', 'date'],
                'due_date'                => ['nullable', 'date'],
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

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $invoice = $this->invoiceService->create($data, $data['items']);

            return response()->json(['success' => true, 'redirection_url' => route('invoices.show', $invoice),  'message' => "Invoice {$invoice->invoice_number} created."]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to create invoice. Please try again.');
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['items', 'payments.allocation.receipt', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        return view('invoices.show', compact('invoice', 'previousBalance'));
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['items', 'payments.allocation.receipt', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        return view('invoices.print', compact('invoice', 'previousBalance'));
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['items', 'payments.allocation.receipt', 'customer']);
        $previousBalance = $this->getPreviousBalance($invoice);
        $pdf = Pdf::loadView('invoices.print', compact('invoice', 'previousBalance'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * Get the customer's outstanding balance from other invoices (before this one).
     */
    private function getPreviousBalance(Invoice $invoice): float
    {
        if ($invoice->previous_balance !== null) {
            return (float) $invoice->previous_balance;
        }

        if (! $invoice->customer_id) {
            return 0;
        }

        $customer = $invoice->customer;
        if (! $customer) {
            return 0;
        }

        return (float) $customer->account_balance - $invoice->remaining_amount;
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
            ->with(['items', 'payments.allocation.receipt', 'customer'])
            ->firstOrFail();
        $previousBalance = $this->getPreviousBalance($invoice);

        return view('invoices.print', compact('invoice', 'previousBalance'));
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $customerId = $invoice->customer_id;
            DB::transaction(function () use ($invoice, $customerId) {
                if ($customerId) {
                    $credits = PaymentHistory::query()
                        ->where('type', 'credit')
                        ->where('source_invoice_id', $invoice->id)
                        ->get();

                    foreach ($credits as $credit) {
                        $this->customerAccountService->discardReceiptAllocations($credit);
                        $credit->delete();
                    }
                }

                $invoice->delete();

                if ($customerId && ($customer = Customer::find($customerId))) {
                    $this->customerAccountService->reconcile($customer);
                }
            });

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete invoice. Please try again.', null, 422);
        }
    }

    public function edit(Invoice $invoice)
    {
        $customers = Customer::active()->orderBy('name')->get();
        $products  = ProductSize::with('product')->active()->get();
        $invoice->load(['items', 'payments', 'customer']);
        $initialPayment = PaymentHistory::query()
            ->where('type', 'receipt')
            ->where('target_invoice_id', $invoice->id)
            ->sum('amount');
        $hasCustomerAllocations = CustomerPaymentAllocation::query()
            ->whereIn('payment_id', $invoice->payments()->pluck('id'))
            ->exists();
        $canEditInitialPayment = ! $hasCustomerAllocations || $initialPayment > 0;
        $initialPayment = $initialPayment ?: $invoice->paid_amount;

        return view('invoices.edit', compact('invoice', 'customers', 'products', 'initialPayment', 'canEditInitialPayment'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'customer_id'             => ['nullable', 'exists:customers,id'],
            'walk_in_name'            => ['nullable', 'string', 'max:255'],
            'walk_in_phone'           => ['nullable', 'string', 'max:15'],
            'discount_amount'         => ['nullable', 'numeric', 'min:0'],
            'tax_amount'              => ['nullable', 'numeric', 'min:0'],
            'return_item_amount'      => ['required', 'numeric', 'min:0'],
            'exchange_item_amount'    => ['required', 'numeric', 'min:0'],
            'nug'                     => ['required', 'integer', 'min:0'],
            'nug_amount'              => ['required', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string'],
            'invoice_date'            => ['nullable', 'date'],
            'due_date'                => ['nullable', 'date'],
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

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->toArray()], 422);
        }
        try {
            $updated = $this->invoiceService->updateInvoice($invoice, $data, $data['items'], !empty($data['update_prices']));
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }  catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Failed to update invoice');
        }

        return response()->json(['success' => true, 'redirection_url' => route('invoices.show', $updated),  'message' => "Invoice {$updated->invoice_number} updated."], 200);
    }
}
