@extends('layouts.app')
@section('title', __('Purchases Report'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Purchases Report') }}</h4>
    <small class="text-muted">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</small>
  </div>
  <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-printer me-1"></i> {{ __('Print') }}
  </button>
</div>

<div class="card mb-3 no-print">
  <div class="card-body py-2">
    <form method="GET" action="{{ route('reports.purchases') }}" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('From Date') }}</label>
        <input type="text" name="from" id="fromDate" class="form-control form-control-sm" value="{{ $from }}">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('To Date') }}</label>
        <input type="text" name="to" id="toDate" class="form-control form-control-sm" value="{{ $to }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">{{ __('Status') }}</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">{{ __('All Status') }}</option>
          <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
          <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>{{ __('Partial') }}</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
        <a href="{{ route('reports.purchases') }}" class="btn btn-sm btn-outline-secondary ms-1">{{ __('Reset') }}</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Invoices') }}</div>
      <div class="fs-4 fw-bold">{{ $summary['count'] }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Purchases') }}</div>
      <div class="fs-5 fw-bold">PKR {{ number_format($summary['total_sales'], 0) }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Paid') }}</div>
      <div class="fs-5 fw-bold text-success">PKR {{ number_format($summary['total_paid'], 0) }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Pending') }}</div>
      <div class="fs-5 fw-bold text-danger">PKR {{ number_format($summary['total_pending'], 0) }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0">{{ __('Invoice Details') }}</h6></div>
  <div class="card-body p-2">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="salesTable">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>{{ __('Invoice #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Vendors') }}</th>
            <th class="text-end">{{ __('Total') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due') }}</th>
            <th>{{ __('Created By') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $i => $inv)
          <tr>
            <td class="text-muted small">{{ $i + 1 }}</td>
            <td><a href="{{ route('invoices.show', $inv) }}" class="fw-semibold">{{ $inv->invoice_number }}</a></td>
            <td>{{ $inv->invoice_date->format('d M Y') }}</td>
            <td>{{ $inv->vendor?->name ?? '' }}</td>
            <td class="text-end">PKR {{ number_format($inv->total_amount, 0) }}</td>
            <td class="text-end text-success">PKR {{ number_format($inv->paid_amount, 0) }}</td>
            <td class="text-end {{ $inv->remaining_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">PKR {{ number_format($inv->remaining_amount, 0) }}</td>
            <td>{{ $inv->createdBy?->name ?? '' }}</td>
            <td>
              @php $badge = match($inv->status) { 'paid' => 'success', 'partial' => 'warning text-dark', 'pending' => 'danger', default => 'secondary' }; @endphp
              <span class="badge bg-{{ $badge }}">{{ __(ucfirst($inv->status)) }}</span>
            </td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light fw-semibold">
          <tr>
            <td colspan="4" class="text-end">{{ __('Total') }}</td>
            <td class="text-end">PKR {{ number_format($summary['total_sales'], 0) }}</td>
            <td class="text-end text-success">PKR {{ number_format($summary['total_paid'], 0) }}</td>
            <td class="text-end text-danger">PKR {{ number_format($summary['total_pending'], 0) }}</td>
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
  $('#salesTable').DataTable({
    paging: false, searching: true, info: false,
    dom: '<"d-flex justify-content-between mb-2"<"d-flex gap-2"B>f>t',
    buttons: [
      { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}' },
      { extend: 'print', className: 'btn btn-sm btn-outline-secondary no-print', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}' },
      { extend: 'csv',   className: 'btn btn-sm btn-outline-info', text: '<i class="bi bi-filetype-csv me-1"></i>{{ __("CSV") }}' }
    ]
  });
});
</script>
@endpush
