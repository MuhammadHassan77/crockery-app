@extends('layouts.app')
@section('title', $customer->name)

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ $customer->name }}</h4>
            <small class="text-muted">{{ __('Customer Profile') }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-receipt me-1"></i> {{ __('New Invoice') }}
            </a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
            </a>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2"
                            style="width:64px;height:64px;">
                            <i class="bi bi-person fs-2 text-primary"></i>
                        </div>
                        <h5 class="mb-0">{{ $customer->name }}</h5>
                        <span class="badge {{ $customer->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $customer->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                    <hr>
                    @if ($customer->phone)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telephone text-muted"></i>
                            <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->phone) }}"
                                class="ms-auto text-success" target="_blank" title="WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        </div>
                    @endif
                    @if ($customer->email)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-envelope text-muted"></i>
                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                        </div>
                    @endif
                    @if ($customer->city)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-geo-alt text-muted"></i>
                            <span>{{ $customer->city }}</span>
                        </div>
                    @endif
                    @if ($customer->address)
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-map text-muted mt-1"></i>
                            <span class="small">{{ $customer->address }}</span>
                        </div>
                    @endif
                    @if ($customer->notes)
                        <div class="alert alert-light border small mt-2 mb-0">
                            <i class="bi bi-sticky me-1"></i>{{ $customer->notes }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @php
                        $totalInvoiced = $customer->invoices->sum('total_amount');
                        $totalPaid = $customer->invoices->sum('paid_amount');
                        $accountBalance = (float) $customer->account_balance;
                    @endphp
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">{{ __('Total Invoiced') }}</span>
                        <strong>PKR {{ number_format($totalInvoiced, 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">{{ __('Total Paid') }}</span>
                        <strong class="text-success">PKR {{ number_format($totalPaid, 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">{{ $accountBalance < 0 ? __('Advance / Credit') : __('Udhar / Due') }}</span>
                        <strong class="{{ $accountBalance > 0 ? 'text-danger' : 'text-success' }}">
                            {{-- PKR {{ number_format(abs($accountBalance), 0) }} --}}
                            PKR {{ number_format($accountBalance, 0) }}
                        </strong>
                    </div>
                </div>
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between">
						<h6 class="mb-0">{{ __('Payment History') }}</h6>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-hover mb-0" id="customerPaymentTable">
								<thead class="table-light">
									<tr>
                                        <th>{{ __('Received') }}</th>
										<th>{{ __('Date') }}</th>
                                        <th>{{ __('Applied To') }}</th>
                                        {{-- <th>{{ __('Unallocated Credit') }}</th> --}}
										<th>{{ __('Received By') }}</th>
										<th>{{ __('Notes') }}</th>
										<th>Payment Method</th>
									</tr>
								</thead>
								<tbody>
									@forelse($customer->paymentHistories->sortByDesc('id') as $pay)
										<tr>
                                            <td class="text-success">PKR {{ number_format($pay->amount, 0) }}</td>
											<td>{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') ?? $pay->created_at->format('d M Y') }}</td>
                                            <td class="small">
                                                @forelse($pay->allocations as $allocation)
                                                    @if ($allocation->payment?->invoice)
                                                        <a href="{{ route('invoices.show', $allocation->payment->invoice) }}">
                                                            {{ $allocation->payment->invoice->invoice_number }}
                                                        </a>
                                                        <span class="text-success">PKR {{ number_format($allocation->amount, 0) }}</span><br>
                                                    @endif
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>
                                            {{-- <td class="text-success">
                                                @if (in_array($pay->type, ['receipt', 'credit'], true))
                                                    @php $unallocated = max(0, (float) $pay->amount - (float) $pay->allocations->sum('amount')); @endphp
                                                    {{ $unallocated > 0 ? 'PKR ' . number_format($unallocated, 0) : '—' }}
                                                @else
                                                    —
                                                @endif
                                            </td> --}}
											<td>{{ $pay->receivedBy->name ?? '' }}</td>
											<td>{{ $pay->notes ?? '' }}</td>
											<td>{{ $pay->payment_method ?? '' }}</td>
										</tr>
									@empty
										<tr>
											<td colspan="7" class="text-center text-muted py-4">
												<i class="bi bi-receipt fs-2 d-block mb-2"></i>{{ __('No payments yet.') }}
											</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Invoice History') }}</h6>
                    <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}"
                        class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i> {{ __('New Invoice') }}
                    </a>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="customerInvoiceTable">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Invoice #') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                    <th class="text-end">{{ __('Paid') }}</th>
                                    <th class="text-end">{{ __('Due') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->invoices as $inv)
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoices.show', $inv) }}" class="fw-semibold text-primary">
                                                {{ $inv->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                                        <td class="text-end">PKR {{ number_format($inv->total_amount, 0) }}</td>
                                        <td class="text-end text-success">PKR {{ number_format($inv->paid_amount, 0) }}
                                        </td>
                                        <td
                                            class="text-end {{ $inv->remaining_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            PKR {{ number_format($inv->remaining_amount, 0) }}
                                        </td>
                                        <td>
                                            @php
                                                $badge = match ($inv->status) {
                                                    'paid' => 'success',
                                                    'partial' => 'warning text-dark',
                                                    'pending' => 'danger',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span
                                                class="badge bg-{{ $badge }}">{{ __(ucfirst($inv->status)) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-light">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>{{ __('No invoices yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <h6 class="mb-3">Add Payment Amount</h6>
                    <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Adaigi ka Tariqa' : 'Payment Method' }}</label>
                    <select name="payment_method" id="payment_method" class="form-select form-select-sm mb-3">
                        @foreach(\App\Enums\PaymentMethod::cases() as $paymentMethod)
                            <option value="{{ $paymentMethod->value }}">{{ $paymentMethod->label() }}</option>
                        @endforeach
                    </select>
                    <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Note' : 'Note' }}</label>
                    <textarea class="form-control" id="note" name="note" row="3" maxlength="500"></textarea>
                    <label class="form-label fw-semibold">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" >
                    <br>
                    <div class="input-group">
                        <span class="input-group-text">Amount</span>
                        <input type="number" name="payment_amount" id="payment_amount" class="form-control" min="1"
                            placeholder="Enter Amount">
                        <button type="button" class="btn btn-sm btn-success" id="addPayment">
                            <i class="bi bi-plus-lg me-1"></i> Add Amount
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6 class="modal-title text-success">
                        <i class="bi bi-check-circle me-1"></i>Add Payment Amount
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <h4 class="mb-0 px-3">{{ __('Customer Profile') }} : {{ $customer->name . "($customer->phone)" }}
                    </h4>
                    <div id="pendingInvoicesContent" class="p-3 text-center text-muted">
                        {{ __('Loading pending invoices...') }}
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form action="{{ route('customers.addPayments', ['customer' => $customer->id]) }}" method="POST"
                        id="confirmPaymentForm">
                        @csrf @method('POST')
                        <input type="hidden" id="amount" name="amount">
                        <input type="hidden" id="form_payment_method" name="form_payment_method">
                        <input type="hidden" id="form_note" name="notes">
                        <input type="hidden" id="form_payment_date" name="payment_date">
                        <button type="submit" id="confirmAddPayment" class="btn btn-sm btn-success"
                            disabled>{{ __('Submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(() => {
                let AddTableButtons = (table, tableId) => {
                    const buttons = new $.fn.dataTable.Buttons(table, {
                        buttons: [
                            { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}' },
                            { extend: 'print', className: 'btn btn-sm btn-outline-secondary', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}' }
                        ]
                    });
                    // $('#customerPaymentTable_wrapper').prepend(buttons.container());
                    $(`#${tableId}_wrapper`).prepend(buttons.container());
                    $(`#${tableId}_length select`).prepend('<option value="-1">All</option>');
                };

                @if ($customer?->invoices?->count())
                    let customerInvoiceTable = $('#customerInvoiceTable').DataTable();
                    AddTableButtons(customerInvoiceTable, 'customerInvoiceTable');
                @endif
                @if ($customer?->paymentHistories?->count())
                    let customerPaymentTable = $('#customerPaymentTable').DataTable();
                    AddTableButtons(customerPaymentTable, 'customerPaymentTable');
                @endif

                function formatCurrency(value) {
                    return parseFloat(value).toLocaleString('en-PK', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });
                }

                function renderPendingInvoices(invoices, amount) {
                    if (!invoices.length) {
                        return '<div class="alert alert-info mb-0">{{ __('No outstanding invoices. The full payment will be saved as customer advance.') }}</div>';
                    }

                    var totalDue = invoices.reduce(function(sum, invoice) {
                        return sum + invoice.remaining_amount;
                    }, 0);

                    var html =
                        '<div class="mb-2 alert alert-warning py-2">{{ __('The invoices listed below will be updated in the Invoices table when you confirm. Calculations are performed on the server to prevent tampering.') }}</div>';
                    html +=
                        '<div class="mb-3 text-muted small">{{ __('Payment will be allocated to oldest pending invoices first.') }}</div>';
                    html +=
                        '<div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr>' +
                        '<th>{{ __('Invoice #') }}</th>' +
                        '<th>{{ __('Date') }}</th>' +
                        '<th class="text-end">{{ __('Total') }}</th>' +
                        '<th class="text-end">{{ __('Paid') }}</th>' +
                        '<th class="text-end">{{ __('Due (calc)') }}</th>' +
                        '<th class="text-end">{{ __('Apply') }}</th>' +
                        '<th class="text-end">{{ __('Remaining') }}</th>' +
                        '<th>{{ __('Status') }}</th>' +
                        '</tr></thead><tbody>';

                    $.each(invoices, function(index, invoice) {
                        // Build server-sourced calculation display using server amounts
                        var dueCalc = 'PKR ' + formatCurrency(invoice.total_amount) + ' - PKR ' +
                            formatCurrency(invoice.paid_amount) + ' = PKR ' + formatCurrency(invoice
                                .remaining_amount);
                        var applyAmount = invoice.apply_amount ? ('PKR ' + formatCurrency(invoice
                            .apply_amount)) : '-';
                        html += '<tr>' +
                            '<td>' + invoice.invoice_number + '</td>' +
                            '<td>' + invoice.invoice_date + '</td>' +
                            '<td class="text-end">PKR ' + formatCurrency(invoice.total_amount) + '</td>' +
                            '<td class="text-end text-success">PKR ' + formatCurrency(invoice.paid_amount) +
                            '</td>' +
                            '<td class="text-end small text-muted">' + dueCalc + '</td>' +
                            '<td class="text-end small">' + applyAmount + '</td>' +
                            '<td class="text-end ' + (invoice.remaining_amount > 0 ? 'text-danger fw-semibold' :
                                'text-muted') + '">PKR ' + formatCurrency(invoice.remaining_amount) + '</td>' +
                            '<td><span class="badge bg-' + (invoice.status === 'partial' ? 'warning text-dark' :
                                invoice.status === 'pending' ? 'danger' : 'success') + '">' + invoice
                            .status_label + '</span></td>' +
                            '</tr>';
                    });

                    html += '</tbody></table></div>';
                    html += '<div class="mt-3"><strong>{{ __('Total outstanding') }}:</strong> PKR ' + formatCurrency(
                        totalDue) + '</div>';

                    if (amount > totalDue) {
                        html +=
                            '<div class="alert alert-info mt-2 mb-0">{{ __('Any amount above the outstanding balance will be saved as customer advance.') }}</div>';
                    }

                    return html;
                }

                $('#addPayment').click(function() {
                    var amount = parseFloat($('#payment_amount').val());
                    if (!amount || amount < 1) {
                        alert('{{ __('Please insert valid amount') }}');
                        return;
                    }

                    $('#amount').val(amount);
                    $('#form_payment_method').val($('#payment_method').val() ?? 'cash');
                    $('#form_payment_date').val($('#payment_date').val());
                    $('#form_note').val($('#note').val() ?? 'cash');
                    $('#confirmAddPayment').prop('disabled', true);
                    $('#pendingInvoicesContent').html(
                        '<div class="text-center py-4">{{ __('Loading pending invoices...') }}</div>');
                    $('#addPaymentModal').modal('show');

                    $.getJSON('{{ route('customers.pendingInvoices', $customer) }}', {
                        amount: amount
                    }, function(response) {
                        var content = renderPendingInvoices(response.invoices || [], amount);
                        $('#pendingInvoicesContent').html(content);
                        $('#confirmAddPayment').prop('disabled', false);
                    }).fail(function() {
                        $('#pendingInvoicesContent').html(
                            '<div class="alert alert-danger mb-0">{{ __('Unable to load pending invoices. Please try again.') }}</div>'
                            );
                        $('#confirmAddPayment').prop('disabled', true);
                    });
                });

                // Submit confirmation via AJAX to receive applied-invoices details and update UI
                $('#confirmPaymentForm').on('submit', function(e) {
                    e.preventDefault();
                    var $btn = $('#confirmAddPayment');
                    var orig = $btn.html();
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> {{ __('Processing...') }}'
                        );

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            if (res && res.success) {
                                // Show brief success inside modal then reload to refresh data
                                $('#pendingInvoicesContent').html(
                                    '<div class="alert alert-success mb-0">' + res.message +
                                    '</div>');
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                alert(res?.message || '{{ __('Unable to apply payments.') }}');
                                $btn.prop('disabled', false).html(orig);
                            }
                        },
                        error: function() {
                            alert('{{ __('Unable to apply payments. Please try again.') }}');
                            $btn.prop('disabled', false).html(orig);
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
