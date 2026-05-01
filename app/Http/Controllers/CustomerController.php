<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $customers = Customer::withCount('invoices')
                ->withSum(['invoices as total_due' => fn($q) => $q->whereIn('status', ['pending', 'partial'])], \DB::raw('total_amount - paid_amount'))
                ->select('customers.*');

            return DataTables::of($customers)
                ->addColumn('invoices_count', fn($c) => $c->invoices_count ?? 0)
                ->addColumn('total_due', fn($c) => 'PKR '.number_format($c->total_due ?? 0, 0))
                ->addColumn('status', fn($c) => $c->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>')
                ->addColumn('actions', fn($c) => '
                    <a href="'.route('customers.show', $c).'" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="'.route('customers.edit', $c).'" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="'.$c->id.'" data-url="'.route('customers.destroy', $c).'" title="Delete"><i class="bi bi-trash"></i></button>
                ')
                ->rawColumns(['status', 'actions'])
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
    }

    public function show(Customer $customer)
    {
        $customer->load(['invoices' => fn($q) => $q->latest()->take(20)]);
        return view('customers.show', compact('customer'));
    }

    /**
     * Return outstanding balance for a customer as JSON (used in invoice create AJAX).
     */
    public function balance(Customer $customer)
    {
        $balance = (float) \App\Models\Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as balance')
            ->value('balance') ?? 0;

        return response()->json([
            'customer_id' => $customer->id,
            'name'        => $customer->name,
            'balance'     => $balance,
            'formatted'   => 'PKR ' . number_format($balance, 0),
            'has_balance' => $balance > 0,
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
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
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
