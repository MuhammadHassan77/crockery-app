@extends('layouts.app')
@section('title', __('Purchase Invoice') . ' ' . $purchase_invoice->invoice_number)

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ $purchase_invoice->invoice_number }}</h4>
            <small class="text-muted">{{ $purchase_invoice?->invoice_date?->format('d M Y') }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($purchase_invoice->created_at?->format('Y-m-d') == now()->format('Y-m-d'))
            <a href="{{ route('purchase_invoices.edit', $purchase_invoice) }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
            </a>
            @endif
            <a href="{{ route('purchase_invoices.print', $purchase_invoice) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> {{ __('Print') }}
            </a>
            {{-- <a href="{{ route('purchase_invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-danger"> --}}
            <a href="{{ route('purchase_invoices.print', $purchase_invoice) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> {{ __('PDF') }}
            </a>
            @php $waMsg = urlencode('Invoice ' . $purchase_invoice->invoice_number . ' - PKR ' . number_format($purchase_invoice->total_amount,0) . ' - View: ' . route('invoice.public', $purchase_invoice->share_token)); @endphp
            <a href="https://wa.me/?text={{ $waMsg }}" target="_blank" class="btn btn-sm btn-success">
                <i class="bi bi-whatsapp me-1"></i> WhatsApp
            </a>
            @if ($purchase_invoice->vendor)
                <a href="{{ route('vendors.show', $purchase_invoice->vendor) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person me-1"></i> {{ __('Vendor') }}
                </a>
            @endif
            <a href="{{ route('purchase_invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left Column: Invoice Details --}}
        <div class="col-lg-8">

            {{-- Invoice Meta --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">{{ __('Vendor') }}</p>
                            @if ($purchase_invoice->vendor)
                                <a href="{{ route('vendors.show', $purchase_invoice->vendor) }}" class="fw-semibold">
                                    {{ $purchase_invoice->vendor->name }}
                                </a>
                                @if ($purchase_invoice->vendor->phone)
                                    <br><a href="tel:{{ $purchase_invoice->vendor->phone }}"
                                        class="small text-muted">{{ $purchase_invoice->vendor->phone }}</a>
                                @endif
                            @else
                                <span
                                    class="fw-semibold">{{ $purchase_invoice->vendor_name ?: '' }}</span>
                            @endif
                        </div>
                        <div class="col-sm-3">
                            <p class="text-muted small mb-1">
                                {{ app()->getLocale() === 'ur' ? 'Raseed ki Tarikh' : 'Invoice Date' }}</p>
                            <span class="fw-semibold">{{ $purchase_invoice->invoice_date->format('d M Y') }}</span>
                        </div>
                        <div class="col-sm-3">
                            <p class="text-muted small mb-1">
                                {{ app()->getLocale() === 'ur' ? 'Maturity Tarikh' : 'Due Date' }}</p>
                            <span
                                class="fw-semibold {{ $purchase_invoice->due_date && $purchase_invoice->due_date->isPast() && $purchase_invoice->status !== 'paid' ? 'text-danger' : '' }}">
                                {{ $purchase_invoice->due_date ? $purchase_invoice->due_date->format('d M Y') : '—' }}
                            </span>
                        </div>
                        @if ($purchase_invoice->notes)
                            <div class="col-12">
                                <p class="text-muted small mb-1">{{ __('Notes') }}</p>
                                <span class="small">{{ $purchase_invoice->notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Ashtya' : 'Items' }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Size') }}</th>
                                    <th class="text-center">{{ app()->getLocale() === 'ur' ? 'Tadadd' : 'Qty' }}</th>
                                    <th class="text-end">
                                        {{ app()->getLocale() === 'ur' ? 'Ikaayi Qeemat/fee kilo' : 'Unit Price/Price Per Kg' }}
                                    </th>
                                    <th class="text-end">
                                        {{ app()->getLocale() === 'ur' ? 'wazan (Kg)' : 'Weight in (Kg)' }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $itemsTotal = 0; @endphp
                                @foreach ($purchase_invoice->items as $i => $item)
                                @php 
                                $rowTotal = $item?->weight > 0 ? $item->weight * $item->unit_price : $item->quantity * $item->unit_price; 
                                $itemsTotal += $rowTotal; 
                                @endphp
                                    <tr>
                                        <td class="text-muted small">{{ $i + 1 }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->size_label ?: '—' }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">PKR {{ number_format($item->unit_price, 0) }}</td>
                                        <td class="text-center">{{ $item->weight ?? 'N/A' }}</td>
                                        <td class="text-end fw-semibold">PKR
                                            {{ number_format($rowTotal, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end fw-semibold">{{ app()->getLocale() === 'ur' ? 'Upar Kul' : 'Items Total' }}</td>
                                    <td class="text-end">PKR {{ number_format($itemsTotal, 0) }}</td>
                                </tr>
                                {{-- <tr>
                                    <td colspan="6" class="text-end fw-semibold">
                                        {{ app()->getLocale() === 'ur' ? 'Upar Kul' : 'Subtotal' }}</td>
                                    <td class="text-end">PKR
                                        {{ number_format($purchase_invoice->total_amount + ($purchase_invoice->discount_amount ?? 0), 0) }}
                                    </td>
                                </tr> --}}
                                @if (($purchase_invoice->discount_amount ?? 0) > 0)
                                    <tr>
                                        <td colspan="6" class="text-end text-muted">{{ __('Discount') }}</td>
                                        <td class="text-end text-danger">- PKR
                                            {{ number_format($purchase_invoice->discount_amount, 0) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">
                                        {{ app()->getLocale() === 'ur' ? 'Bara Kul' : 'Grand Total' }}</td>
                                    <td class="text-end fw-bold fs-5">PKR {{ number_format($purchase_invoice->total_amount, 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Adaigi ki Tarikh' : 'Payment History' }}</h6>
                    @if ($purchase_invoice->status !== 'paid')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="bi bi-plus-lg me-1"></i>
                            {{ app()->getLocale() === 'ur' ? 'Adaigi Daalo' : 'Add Payment' }}
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="paymentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ app()->getLocale() === 'ur' ? 'Tariqa' : 'Method' }}</th>
                                    <th class="text-end">{{ app()->getLocale() === 'ur' ? 'Raqam' : 'Amount' }}</th>
                                    <th>{{ __('Notes') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchase_invoice->purchase_payments as $payment)
                                    <tr id="payment-row-{{ $payment->id }}">
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ $payment->payment_method_label }}</span>
                                        </td>
                                        <td class="text-end fw-semibold text-success">PKR
                                            {{ number_format($payment->amount, 0) }}</td>
                                        <td class="small text-muted">{{ $payment->notes ?: '—' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-danger btn-delete-payment"
                                                data-id="{{ $payment->id }}"
                                                data-url="{{ route('purchase_payments.destroy', $payment) }}"
                                                title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noPaymentRow">
                                        <td colspan="5" class="text-center text-muted py-3">
                                            {{ app()->getLocale() === 'ur' ? 'Koi adaigi nahi ki gayi.' : 'No payments recorded.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column: Summary --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Adaigi ka Khulasa' : 'Payment Summary' }}
                        </h6>
                        @php
                            $badge = match ($purchase_invoice->status) {
                                'paid' => 'success',
                                'partial' => 'warning text-dark',
                                'pending' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $badge }} fs-6"
                            id="statusBadge">{{ __(ucfirst($purchase_invoice->status)) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Kul Raqam' : 'Total Amount' }}</span>
                        <strong>PKR {{ number_format($purchase_invoice->total_amount, 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Adaa Shuda' : 'Total Paid' }}</span>
                        <strong class="text-success" id="totalPaidDisplay">PKR
                            {{ number_format($purchase_invoice->paid_amount, 0) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span
                            class="fw-semibold">{{ app()->getLocale() === 'ur' ? 'Is Raseed ka Baqi' : 'This Invoice Due' }}</span>
                        <strong class="{{ $purchase_invoice->remaining_amount > 0 ? 'text-danger' : 'text-success' }} fs-5"
                            id="remainingDisplay">
                            PKR {{ number_format($purchase_invoice->remaining_amount, 0) }}
                        </strong>
                    </div>

                    @if ($purchase_invoice->remaining_amount > 0)
                        <div class="mb-2">
                            <div class="progress" style="height:6px">
                                @php $pct = $purchase_invoice->total_amount > 0 ? min(100, ($purchase_invoice->paid_amount / $purchase_invoice->total_amount) * 100) : 0; @endphp
                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                            </div>
                            <small class="text-muted">{{ number_format($pct, 1) }}%
                                {{ app()->getLocale() === 'ur' ? 'adaa' : 'paid' }}</small>
                        </div>
                    @endif

                    {{-- Previous balance from other invoices --}}
                    @if ($previousBalance > 0)
                        <div class="alert alert-warning py-2 px-3 mb-2 d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold">
                                <i class="bi bi-clock-history me-1"></i>
                                {{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi (Doosri Raseeden)' : 'Prev. Balance (Other Invoices)' }}
                            </span>
                            <span class="fw-bold text-danger">PKR {{ number_format($previousBalance, 0) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span
                                class="fw-bold">{{ app()->getLocale() === 'ur' ? 'Kul Baqi (Sab Milaa kar)' : 'Total Outstanding' }}</span>
                            <strong class="text-danger fs-5">PKR
                                {{ number_format($purchase_invoice->remaining_amount + $previousBalance, 0) }}</strong>
                        </div>
                    @endif

                </div>
            </div>

            {{-- Share Link --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-2"><i class="bi bi-share me-1"></i>
                        {{ app()->getLocale() === 'ur' ? 'Raseed Sharo Karo' : 'Share Invoice' }}</h6>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="shareLink"
                            value="{{ route('purchase_invoice.public', $purchase_invoice->share_token) }}" readonly>
                        <button class="btn btn-outline-secondary" id="btnCopyLink" type="button"
                            title="{{ app()->getLocale() === 'ur' ? 'Copy' : 'Copy' }}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <a href="{{ route('purchase_invoice.public', $purchase_invoice->share_token) }}" target="_blank"
                            class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-eye me-1"></i> {{ app()->getLocale() === 'ur' ? 'Dekhein' : 'Preview' }}
                        </a>
                        <a href="https://wa.me/?text={{ $waMsg }}" target="_blank"
                            class="btn btn-sm btn-success flex-fill">
                            <i class="bi bi-whatsapp me-1"></i> {{ app()->getLocale() === 'ur' ? 'Sharo Karo' : 'Share' }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">{{ app()->getLocale() === 'ur' ? 'Jaldi Amal' : 'Quick Actions' }}</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('purchase_invoices.print', $purchase_invoice) }}" target="_blank"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer me-1"></i>
                            {{ app()->getLocale() === 'ur' ? 'Raseed Print Karo' : 'Print Invoice' }}
                        </a>
                        <a href="{{ route('purchase_invoices.pdf', $purchase_invoice) }}" target="_blank"
                            class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i>
                            {{ app()->getLocale() === 'ur' ? 'PDF Utharao' : 'Download PDF' }}
                        </a>
                        @if ($purchase_invoice->vendor)
                            <a href="{{ route('purchase_invoices.create', ['vendor_id' => $purchase_invoice->vendor_id]) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-receipt me-1"></i>
                                {{ app()->getLocale() === 'ur' ? 'Nai Raseed (Usi Grahak ki)' : 'New Invoice (Same Vendor)' }}
                            </a>
                        @endif
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#deleteInvoiceModal">
                            <i class="bi bi-trash me-1"></i>
                            {{ app()->getLocale() === 'ur' ? 'Raseed Mitao' : 'Delete Invoice' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Payment Modal --}}
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-cash-coin me-1"></i>
                        {{ app()->getLocale() === 'ur' ? 'Adaigi Daalo' : 'Add Payment' }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        {{ app()->getLocale() === 'ur' ? 'Baqi Raqam:' : 'Remaining due:' }} <strong
                            id="remainingDue">PKR {{ number_format($purchase_invoice->remaining_amount, 0) }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Raqam' : 'Amount' }}
                            <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">PKR</span>
                            <input type="number" id="payAmount" class="form-control" min="1"
                                max="{{ $purchase_invoice->remaining_amount }}" step="1"
                                value="{{ $purchase_invoice->remaining_amount }}" required>
                        </div>
                        <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Zyada se zyada:' : 'Max:' }} PKR
                            {{ number_format($purchase_invoice->remaining_amount, 0) }}</div>
                    </div>
                    <div class="mb-3">
                        <label
                            class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Adaigi ka Tariqa' : 'Payment Method' }}</label>
                        <select id="payMethod" class="form-select">
                            <option value="cash" selected>{{ app()->getLocale() === 'ur' ? 'Naqdh' : 'Cash' }}</option>
                            <option value="bank_transfer">
                                {{ app()->getLocale() === 'ur' ? 'Bank Tabadla' : 'Bank Transfer' }}</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="cheque">{{ app()->getLocale() === 'ur' ? 'Cheque' : 'Cheque' }}</option>
                            <option value="other">{{ app()->getLocale() === 'ur' ? 'Aur' : 'Other' }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label
                            class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Adaigi ki Tarikh' : 'Payment Date' }}</label>
                        <input type="text" id="payDate" class="form-control"
                            value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                        <input type="text" id="payNotes" class="form-control"
                            placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari' : 'Optional' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btnSavePayment">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ app()->getLocale() === 'ur' ? 'Adaigi Mehfooz Karo' : 'Save Payment' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Invoice Modal --}}
    <div class="modal fade" id="deleteInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i>
                        {{ app()->getLocale() === 'ur' ? 'Raseed Mitao' : 'Delete Invoice' }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    {{ app()->getLocale() === 'ur' ? 'Yeh raseed aur tamam adaigian hamesha k liye mit jaengi. Pakka hai?' : 'This will permanently delete the invoice and all payments. Are you sure?' }}
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form action="{{ route('purchase_invoices.destroy', $purchase_invoice) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            var totalAmount = {{ $purchase_invoice->total_amount }};

            flatpickr('#payDate', {
                dateFormat: 'Y-m-d'
            });

            $('#btnCopyLink').on('click', function() {
                var link = $('#shareLink').val();
                navigator.clipboard.writeText(link).then(function() {
                    var btn = $('#btnCopyLink');
                    btn.html('<i class="bi bi-check-lg text-success"></i>');
                    setTimeout(function() {
                        btn.html('<i class="bi bi-clipboard"></i>');
                    }, 2000);
                });
            });

            $('#btnSavePayment').on('click', function() {
                var amount = parseFloat($('#payAmount').val());
                if (!amount || amount <= 0) {
                    alert(
                        '{{ app()->getLocale() === 'ur' ? 'Sahi raqam likhein.' : 'Please enter a valid amount.' }}');
                    return;
                }

                var btn = $(this).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>{{ app()->getLocale() === 'ur' ? 'Mehfooz...' : 'Saving...' }}'
                    );

                $.ajax({
                    url: '{{ route('purchase_payments.store', $purchase_invoice) }}',
                    method: 'POST',
                    data: {
                        amount: amount,
                        payment_method: $('#payMethod').val(),
                        payment_date: $('#payDate').val(),
                        notes: $('#payNotes').val()
                    },
                    success: function(res) {
                        $('#noPaymentRow').remove();
                        var row = '<tr id="payment-row-' + res.payment.id + '">' +
                            '<td>' + res.payment.payment_date + '</td>' +
                            '<td><span class="badge bg-light text-dark border">' + res.payment
                            .payment_method_label + '</span></td>' +
                            '<td class="text-end fw-semibold text-success">PKR ' + res.payment
                            .amount_formatted + '</td>' +
                            '<td class="small text-muted">' + (res.payment.notes || '—') +
                            '</td>' +
                            '<td class="text-end"><button class="btn btn-sm btn-outline-danger btn-delete-payment"' +
                            ' data-id="' + res.payment.id + '"' +
                            ' data-url="' + res.payment.delete_url + '">' +
                            '<i class="bi bi-trash"></i></button></td>' +
                            '</tr>';
                        $('#paymentsTable tbody').append(row);

                        $('#totalPaidDisplay').text('PKR ' + res.invoice.paid_formatted);
                        $('#remainingDisplay').text('PKR ' + res.invoice.remaining_formatted)
                            .removeClass('text-danger text-success')
                            .addClass(res.invoice.remaining > 0 ? 'text-danger' :
                                'text-success');
                        $('#remainingDue').text('PKR ' + res.invoice.remaining_formatted);
                        $('#payAmount').val(res.invoice.remaining).attr('max', res.invoice
                            .remaining);

                        var badgeClass = {
                            paid: 'success',
                            partial: 'warning text-dark',
                            pending: 'danger'
                        };
                        $('#statusBadge').attr('class', 'badge fs-6 bg-' + (badgeClass[res
                                .invoice.status] || 'secondary'))
                            .text(res.invoice.status_label);

                        if (res.invoice.status === 'paid') {
                            $('[data-bs-target="#addPaymentModal"]').hide();
                        }

                        $('#addPaymentModal').modal('hide');
                        $('#payNotes').val('');
                        showToast(
                            '{{ app()->getLocale() === 'ur' ? 'Adaigi mehfooz ho gayi!' : 'Payment saved successfully!' }}',
                            'success');
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr
                            .responseJSON.message :
                            '{{ app()->getLocale() === 'ur' ? 'Adaigi mehfooz nahi ho saki.' : 'Failed to save payment.' }}';
                        showToast(msg, 'danger');
                    },
                    complete: function() {
                        $('#btnSavePayment').prop('disabled', false).html(
                            '<i class="bi bi-check-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Adaigi Mehfooz Karo' : 'Save Payment' }}'
                            );
                    }
                });
            });

            $(document).on('click', '.btn-delete-payment', function() {
                if (!confirm(
                        '{{ app()->getLocale() === 'ur' ? 'Adaigi mitao? Raseed ki halat update ho jaegi.' : 'Delete this payment? The invoice status will be updated.' }}'
                        )) return;
                var btn = $(this);
                var url = btn.data('url');
                var rowId = 'payment-row-' + btn.data('id');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _method: 'DELETE'
                    },
                    success: function(res) {
                        $('#' + rowId).fadeOut(300, function() {
                            $(this).remove();
                        });

                        $('#totalPaidDisplay').text('PKR ' + res.invoice.paid_formatted);
                        $('#remainingDisplay').text('PKR ' + res.invoice.remaining_formatted)
                            .removeClass('text-danger text-success')
                            .addClass(res.invoice.remaining > 0 ? 'text-danger' :
                                'text-success');
                        $('#remainingDue').text('PKR ' + res.invoice.remaining_formatted);
                        $('#payAmount').val(res.invoice.remaining).attr('max', res.invoice
                            .remaining);

                        var badgeClass = {
                            paid: 'success',
                            partial: 'warning text-dark',
                            pending: 'danger'
                        };
                        $('#statusBadge').attr('class', 'badge fs-6 bg-' + (badgeClass[res
                                .invoice.status] || 'secondary'))
                            .text(res.invoice.status_label);

                        if (res.invoice.status !== 'paid') {
                            $('[data-bs-target="#addPaymentModal"]').show();
                        }

                        if ($('#paymentsTable tbody tr').length === 0) {
                            $('#paymentsTable tbody').append(
                                '<tr id="noPaymentRow"><td colspan="5" class="text-center text-muted py-3">{{ app()->getLocale() === 'ur' ? 'Koi adaigi nahi ki gayi.' : 'No payments recorded.' }}</td></tr>'
                                );
                        }

                        showToast(
                            '{{ app()->getLocale() === 'ur' ? 'Adaigi mit gayi.' : 'Payment deleted.' }}',
                            'warning');
                    },
                    error: function() {
                        showToast(
                            '{{ app()->getLocale() === 'ur' ? 'Adaigi nahi miti.' : 'Failed to delete payment.' }}',
                            'danger');
                    }
                });
            });

            function showToast(msg, type) {
                var toast = $('<div class="toast align-items-center text-bg-' + type +
                    ' border-0 show position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:9999">' +
                    '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                    '</div></div>');
                $('body').append(toast);
                setTimeout(function() {
                    toast.remove();
                }, 3000);
            }
        });
    </script>
@endpush
