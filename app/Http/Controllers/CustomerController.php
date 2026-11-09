<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Controllers\Traits\SelectFieldTrait;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use SelectFieldTrait;

    public function __construct(private CustomerAccountService $customerAccountService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $customers = Customer::select('customers.*')
                ->withCount('invoices');

            return DataTables::of($customers)
                ->orderColumn('total_due', fn($query, $order) => $query->orderBy('account_balance', $order))
                ->addColumn('invoices_count', fn($c) => $c->invoices_count ?? 0)
                ->addColumn('total_due', fn($c) => (float) $c->account_balance < 0
                    ? '<span class="text-success">Advance PKR ' . number_format(abs((float) $c->account_balance), 0) . '</span>'
                    : '<span class="text-danger">PKR ' . number_format((float) $c->account_balance, 0) . '</span>')
                ->addColumn('status', fn($c) => $c->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>')
                ->addColumn('actions', fn($c) => '
                    <a href="' . route('customers.show', $c) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="' . route('customers.edit', $c) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $c->id . '" data-url="' . route('customers.destroy', $c) . '" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->rawColumns(['total_due', 'status', 'actions'])
                ->make(true);
        }

        return view('customers.index');
    }

    public function create()
    {
        return view('customers.create');
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

            Customer::create($data);

            return redirect()->route('customers.index')->with('success', 'Customer added.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to add customer. Please try again.');
        }
    }

    public function show(Customer $customer)
    {
        if (request()->createInvoice) {
            return response()->json($customer, 200);
        }
        $customer->load([
            'invoices' => fn($q) => $q->latest(),// ->take(20),
            'paymentHistories.receivedBy',
            'paymentHistories.allocations.payment.invoice',
        ]);

        // dd($customer->paymentHistories);

        return view('customers.show', compact('customer'));
    }

    public function addPayments(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01'],
                'form_payment_method' => ['required', 'string', 'max:50'],
                'payment_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $receipt = $this->customerAccountService->receive($customer, [
                'amount' => $data['amount'],
                'payment_method' => $data['form_payment_method'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);
            $allocated = (float) $receipt->allocations->sum('amount');
            $advance = max(0, (float) $receipt->amount - $allocated);
            $message = 'Received PKR ' . number_format((float) $receipt->amount, 0)
                . ', applied PKR ' . number_format($allocated, 0) . ' across '
                . $receipt->allocations->count() . ' invoice(s).';
            if ($advance > 0) {
                $message .= ' PKR ' . number_format($advance, 0) . ' is available as customer advance.';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'applied' => $receipt->allocations->map(fn($allocation) => [
                        'invoice_id' => $allocation->payment->invoice_id,
                        'invoice_number' => $allocation->payment->invoice->invoice_number,
                        'applied_amount' => (float) $allocation->amount,
                    ]),
                    'applied_total' => $allocated,
                    'advance' => $advance,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to apply payments. Please try again.');
        }
    }

    public function pendingInvoices(Request $request, Customer $customer)
    {
        $amount = (float) ($request->query('amount') ?? 0);

        $invoices = $customer->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        // If amount provided, compute a server-side allocation preview
        $remainingToAllocate = max(0, $amount);

        $preview = $invoices->map(function ($invoice) use (&$remainingToAllocate) {
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
     * Return outstanding balance for a customer as JSON (used in invoice create AJAX).
     */
    public function balance(Customer $customer)
    {
        $balance = (float) $customer->account_balance;

        return response()->json([
            'customer_id' => $customer->id,
            'name'        => $customer->name,
            'balance'     => $balance,
            'formatted'   => 'PKR ' . number_format(abs($balance), 0),
            'has_balance' => $balance > 0,
            'has_advance' => $balance < 0,
            'advance'     => max(0, -$balance),
        ]);
    }

    /**
     * Search customers for Select2 dropdown
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
        $customers = Customer::where(function ($q) use ($query) {
            $q->where('name', 'like', "%$query%")
                ->orWhere('phone', 'like', "%$query%")
                ->orWhere('email', 'like', "%$query%");
        })
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name', 'phone', 'email');

        // Format the results using custom formatter
        $results = $this->formatSelectResults($customers, $page, function ($customer) {
            return [
                'id'   => $customer->id,
                'text' => "{$customer->name}" . ($customer->phone ? " ({$customer->phone})" : "")
            ];
        });

        return response()->json($results);
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
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

            $customer->update($data);

            return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
        } catch (\Throwable $e) {
            return $this->handleException($request, $e, 'Unable to update customer. Please try again.');
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Customer deleted.');
        } catch (\Throwable $e) {
            return $this->handleException(request(), $e, 'Unable to delete customer. Please try again.');
        }
    }
}
