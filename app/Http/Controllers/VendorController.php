<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Http\Controllers\Traits\SelectFieldTrait;
use App\Models\PaymentHistory;
use App\Models\PurchaseInvoice;
use App\Services\PurchasePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    use SelectFieldTrait;

    public function __construct(private PurchasePaymentService $purchasePaymentService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $vendors = Vendor::select('vendors.*')
                ->withSum(['purchase_invoices as total_due' => fn($q) => $q->whereIn('status', ['pending', 'partial'])], DB::raw('total_amount - paid_amount'))
                ->withCount('purchase_invoices');

            return DataTables::of($vendors)
                ->addColumn('purchase_invoices_count', fn($c) => $c->purchase_invoices_count ?? 0)
                ->addColumn('total_due', fn($c) => 'PKR ' . number_format($c->total_due ?? 0, 0))
                ->addColumn('status', fn($c) => $c->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>')
                ->addColumn('actions', fn($c) => '
                    <a href="' . route('vendors.show', $c) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="' . route('vendors.edit', $c) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $c->id . '" data-url="' . route('vendors.destroy', $c) . '" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('vendors.index');
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'    => ['required', 'string', 'max:255'],
                'phone'   => ['nullable', 'string', 'max:20'],
                'email'   => ['nullable', 'email'],
                'address' => ['nullable', 'string'],
                'city'    => ['nullable', 'string'],
                'notes'   => ['nullable', 'string'],
            ]);

            Vendor::create($data);

            return redirect()->route('vendors.index')->with('success', 'Vendor added.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to add vendor. Please try again.');
        }
    }

    public function show(Vendor $vendor)
    {
        if (request()->createInvoice) {
            return response()->json($vendor, 200);
        }

        $vendor->load([
            'purchase_invoices' => fn($q) => $q->latest(), // ->take(20),
            'paymentHistories.receivedBy',
        ]);

        // dd($vendor->paymentHistories);

        return view('vendors.show', compact('vendor'));
    }

    public function addPayments(Request $request, Vendor $vendor)
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01'],
                'form_payment_method' => ['required']
            ]);

            $amount = (float) $data['amount'];
            $payment_method = (string) $data['form_payment_method'];
            $pendingInvoices = $vendor->purchase_invoices()
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('created_at')
                ->get();

            if ($pendingInvoices->isEmpty()) {
                return back()->with('error', 'No pending or partial invoices found for this vendor.');
            }

            $totalDue = $pendingInvoices->sum(fn($inv) => $inv->remaining_amount);
            $toApply = min($amount, $totalDue);
            $remainingPayment = $toApply;
            $appliedCount = 0;

            $applied = [];

            DB::transaction(function () use ($pendingInvoices, $vendor, $payment_method, &$remainingPayment, &$appliedCount, &$applied) {
                foreach ($pendingInvoices as $invoice) {
                    if ($remainingPayment <= 0) {
                        break;
                    }

                    $invoiceRemaining = $invoice->remaining_amount;
                    if ($invoiceRemaining <= 0) {
                        continue;
                    }

                    $paymentAmount = min($invoiceRemaining, $remainingPayment);
                    $this->purchasePaymentService->record($invoice, [
                        'amount'         => $paymentAmount,
                        'payment_method' => $payment_method ?? 'cash',
                        'paid_at'        => now(),
                        'notes'          => 'Auto-applied payment from vendor profile',
                    ]);

                    $invoice->refresh();

                    $applied[] = [
                        'invoice_id'      => $invoice->id,
                        'invoice_number'  => $invoice->invoice_number,
                        'applied_amount'  => $paymentAmount,
                        'remaining_after' => $invoice->remaining_amount,
                        'status'          => $invoice->status,
                    ];

                    $remainingPayment -= $paymentAmount;
                    $appliedCount++;
                }
                PaymentHistory::create([
                    'model_id' => $vendor?->id,
                    'model_name' => $vendor?->getMorphClass(),
                    'payment_method' => $payment_method ?? 'cash',
                    'amount' => request()->amount ?? 0,
                    'received_by' => auth()->user()->id,
                    'payment_date' => request()?->payment_date ?? now(),
                    'notes' => request()?->notes ?? null,
                ]);
            });

            $message = 'Applied PKR ' . number_format($toApply, 0) . ' across ' . $appliedCount . ' invoice' . ($appliedCount === 1 ? '' : 's') . '.';
            if ($amount > $toApply) {
                $message .= ' Excess amount was ignored because total outstanding balance is only PKR ' . number_format($totalDue, 0) . '.';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'applied' => $applied,
                    'applied_total' => $toApply,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to apply payments. Please try again.');
        }
    }

    public function pendingInvoices(Request $request, Vendor $vendor)
    {
        $amount = (float) ($request->query('amount') ?? 0);

        $purchase_invoices = $vendor->purchase_invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('created_at')
            ->get();

        // If amount provided, compute a server-side allocation preview
        $remainingToAllocate = max(0, $amount);

        $preview = $purchase_invoices->map(function ($invoice) use (&$remainingToAllocate) {
            $apply = 0;
            $remaining = $invoice->remaining_amount;
            if ($remainingToAllocate > 0) {
                $apply = min($remaining, $remainingToAllocate);
                $remainingToAllocate -= $apply;
            }

            return [
                'id'               => $invoice->id,
                'invoice_number'   => $invoice->invoice_number,
                'invoice_date'     => $invoice->invoice_date?->format('d M Y'),
                'total_amount'     => $invoice->total_amount,
                'paid_amount'      => $invoice->paid_amount,
                'remaining_amount' => $invoice->remaining_amount,
                'status'           => $invoice->status,
                'status_label'     => ucfirst($invoice->status),
                // Server-side calculation string to show due amount as a calculation expression
                'due_calc'         => $invoice->total_amount . ' - ' . $invoice->paid_amount . ' = ' . $invoice->remaining_amount,
                // Allocation preview: amount that would be applied to this invoice
                'apply_amount'     => $apply,
            ];
        });

        return response()->json(['invoices' => $preview]);
    }

    /**
     * Return outstanding balance for a vendor as JSON (used in invoice create AJAX).
     */
    public function balance(Vendor $vendor)
    {
        $balance = (float) PurchaseInvoice::where('vendor_id', $vendor->id)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as balance')
            ->value('balance') ?? 0;

        return response()->json([
            'vendor_id' => $vendor->id,
            'name'        => $vendor->name,
            'balance'     => $balance,
            'formatted'   => 'PKR ' . number_format($balance, 0),
            'has_balance' => $balance > 0,
        ]);
    }

    /**
     * Search vendors for Select2 dropdown
     * Supports search by name, phone, or email with pagination
     */
    public function search(Request $request)
    {
        $request->validate([
            'q'    => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1']
        ]);

        $query = $this->sanitizeSearchQuery($request->q);
        $page = $request->page ?? 1;

        // Build search query
        $vendors = Vendor::where(function ($q) use ($query) {
            $q->where('name', 'like', "%$query%")
                ->orWhere('phone', 'like', "%$query%")
                ->orWhere('email', 'like', "%$query%");
        })
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name', 'phone', 'email');

        // Format the results using custom formatter
        $results = $this->formatSelectResults($vendors, $page, function ($vendor) {
            return [
                'id'   => $vendor->id,
                'text' => "{$vendor->name}" . ($vendor->phone ? " ({$vendor->phone})" : "")
            ];
        });

        return response()->json($results);
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        try {
            $data = $request->validate([
                'name'      => ['required', 'string', 'max:255'],
                'phone'     => ['nullable', 'string', 'max:20'],
                'email'     => ['nullable', 'email'],
                'address'   => ['nullable', 'string'],
                'city'      => ['nullable', 'string'],
                'notes'     => ['nullable', 'string'],
                'is_active' => ['boolean'],
            ]);

            $vendor->update($data);

            return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor updated.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to update vendor. Please try again.');
        }
    }

    public function destroy(Vendor $vendor)
    {
        try {
            $vendor->delete();
            return redirect()->route('vendors.index')->with('success', 'Vendor deleted.');
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete vendor. Please try again.');
        }
    }
}
