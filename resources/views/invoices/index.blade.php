@extends('layouts.app')
@section('title', __('Invoices'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Invoices') }}</h4>
    <small class="text-muted">{{ __('All sales and billing records') }}</small>
  </div>
  <a href="{{ route('invoices.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> {{ __('New Invoice') }}
  </a>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">{{ __('Date Range') }}</label>
        <input type="text" id="dateRange" class="form-control form-control-sm" placeholder="{{ __('Select date range') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">{{ __('Status') }}</label>
        <select id="statusFilter" class="form-select form-select-sm">
          <option value="">{{ __('All Status') }}</option>
          <option value="paid">{{ __('Paid') }}</option>
          <option value="partial">{{ __('Partial') }}</option>
          <option value="pending">{{ __('Pending') }}</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">{{ __('Customer Type') }}</label>
        <select id="typeFilter" class="form-select form-select-sm">
          <option value="">{{ __('All Types') }}</option>
          <option value="walkin">{{ __('Walk-in') }}</option>
          <option value="regular">{{ __('Regular') }}</option>
        </select>
      </div>
      <div class="col-auto">
        <button id="btnFilter" class="btn btn-sm btn-primary">
          <i class="bi bi-funnel me-1"></i>{{ __('Filter') }}
        </button>
        <button id="btnReset" class="btn btn-sm btn-outline-secondary ms-1">
          <i class="bi bi-x-circle me-1"></i>{{ __('Reset') }}
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="invoicesTable" class="table table-hover mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th>{{ __('Invoice #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Customers') }}</th>
            <th>{{ __('Type') }}</th>
            <th class="text-end">{{ __('Total') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="text-center">{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('Delete Invoice') }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-0">{{ __('This will permanently delete the invoice and all payments. Are you sure?') }}</div>
      <div class="modal-footer border-0">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <form id="deleteForm" method="POST">
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
$(function () {
  flatpickr('#dateRange', { mode: 'range', dateFormat: 'Y-m-d', allowInput: true });

  var table = $('#invoicesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route('invoices.index') }}',
      data: function (d) {
        var dr = $('#dateRange').val().split(' to ');
        d.date_from = dr[0] || '';
        d.date_to   = dr[1] || dr[0] || '';
        d.status    = $('#statusFilter').val();
        d.type      = $('#typeFilter').val();
      }
    },
    columns: [
      { data: 'invoice_number', name: 'invoice_number' },
      { data: 'invoice_date',   name: 'invoice_date' },
      { data: 'customer_name',  name: 'customer_name', defaultContent: '<span class="text-muted">{{ __("Walk-in") }}</span>' },
      { data: 'type_badge',     name: 'customer_id',   searchable: false, orderable: false },
      { data: 'total_amount',   name: 'total_amount',  className: 'text-end' },
      { data: 'paid_amount',    name: 'paid_amount',   className: 'text-end text-success' },
      { data: 'remaining',      name: 'remaining',     className: 'text-end', searchable: false },
      { data: 'status_badge',   name: 'status',        searchable: false },
      { data: 'actions',        name: 'actions',       orderable: false, searchable: false, className: 'text-center' }
    ],
    order: [[1, 'desc']],
    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex gap-2"Bf><"d-flex align-items-center gap-2"l>>rt<"d-flex justify-content-between mt-3"ip>',
    buttons: [
      { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}', exportOptions: { columns: [0,1,2,3,4,5,6] } },
      { extend: 'print', className: 'btn btn-sm btn-outline-secondary', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}', exportOptions: { columns: [0,1,2,3,4,5,6] } }
    ],
    pageLength: 20,
    language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
  });

  $('#btnFilter').on('click', function () { table.ajax.reload(); });
  $('#btnReset').on('click', function () {
    $('#dateRange').val(''); $('#statusFilter').val(''); $('#typeFilter').val('');
    table.ajax.reload();
  });
  $('#statusFilter, #typeFilter').on('change', function () { table.ajax.reload(); });
  $(document).on('click', '.btn-delete', function () {
    $('#deleteForm').attr('action', $(this).data('url'));
    $('#deleteModal').modal('show');
  });
});
</script>
@endpush
