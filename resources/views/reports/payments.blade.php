@extends('layouts.app')
@section('title', __('Payments Report'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Payments Report') }}</h4>
    <small class="text-muted">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</small>
  </div>
  <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
    <i class="bi bi-printer me-1"></i> {{ __('Print') }}
  </button>
</div>

<div class="card mb-3 no-print">
  <div class="card-body py-2">
    <form method="GET" action="{{ route('reports.payments') }}" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('From Date') }}</label>
        <input type="text" name="from" id="fromDate" class="form-control form-control-sm" value="{{ $from }}">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('To Date') }}</label>
        <input type="text" name="to" id="toDate" class="form-control form-control-sm" value="{{ $to }}">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
        <a href="{{ route('reports.payments') }}" class="btn btn-sm btn-outline-secondary ms-1">{{ __('Reset') }}</a>
      </div>
    </form>
  </div>
</div>

@php $totalCollected = $payments->sum('amount'); $byMethod = $payments->groupBy('payment_method')->map->sum('amount'); @endphp

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Collected') }}</div>
      <div class="fs-4 fw-bold text-success">PKR {{ number_format($totalCollected, 0) }}</div>
      <div class="text-muted small">{{ $payments->count() }} {{ app()->getLocale() === 'ur' ? 'Adaigian' : 'payments' }}</div>
    </div></div>
  </div>
  @foreach($byMethod as $method => $amount)
  <div class="col-md-4">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __(ucfirst(str_replace('_', ' ', $method))) }}</div>
      <div class="fs-5 fw-bold">PKR {{ number_format($amount, 0) }}</div>
      <div class="text-muted small">{{ $payments->where('payment_method', $method)->count() }} {{ app()->getLocale() === 'ur' ? 'Adaigian' : 'payments' }}</div>
    </div></div>
  </div>
  @endforeach
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0">{{ __('Payment Records') }}</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="paymentsTable">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Invoice #') }}</th>
            <th>{{ __('Customers') }}</th>
            <th>{{ __('Payment Method') }}</th>
            <th class="text-end">{{ __('Total Amount') }}</th>
            <th>{{ __('Notes') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($payments as $i => $payment)
          <tr>
            <td class="text-muted small">{{ $i + 1 }}</td>
            <td>{{ $payment->paid_at->format('d M Y') }}</td>
            <td><a href="{{ route('invoices.show', $payment->invoice) }}" class="fw-semibold">{{ $payment->invoice->invoice_number }}</a></td>
            <td>{{ $payment->invoice->customer?->name ?? ($payment->invoice->customer_name ?: (app()->getLocale() === 'ur' ? 'Seedha Aaya' : 'Walk-in')) }}</td>
            <td><span class="badge bg-light text-dark border">{{ __(ucfirst(str_replace('_', ' ', $payment->payment_method))) }}</span></td>
            <td class="text-end fw-semibold text-success">PKR {{ number_format($payment->amount, 0) }}</td>
            <td class="small text-muted">{{ $payment->notes ?: '—' }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="5" class="text-end">{{ __('Total') }}</td>
            <td class="text-end text-success">PKR {{ number_format($totalCollected, 0) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  flatpickr('#fromDate', { dateFormat: 'Y-m-d' });
  flatpickr('#toDate',   { dateFormat: 'Y-m-d' });
  $('#paymentsTable').DataTable({
    paging: false, searching: true, info: false, order: [[1, 'desc']],
    dom: '<"d-flex justify-content-between mb-2"<"d-flex gap-2"B>f>t',
    buttons: [
      { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}' },
      { extend: 'csv',   className: 'btn btn-sm btn-outline-info',    text: '<i class="bi bi-filetype-csv me-1"></i>{{ __("CSV") }}' },
      { extend: 'print', className: 'btn btn-sm btn-outline-secondary no-print', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}' }
    ]
  });
});
</script>
@endpush
