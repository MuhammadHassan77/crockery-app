@extends('layouts.app')
@section('title', __('Purchase Invoices'))

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ __('Purchase Invoices') }}</h4>
            <small class="text-muted">{{ __('All sales and billing records') }}</small>
        </div>
        <a href="{{ route('purchase_invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> {{ __('New Purchase Invoice') }}
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ __('Date Range') }}</label>
                    <input type="text" id="dateRange" class="form-control form-control-sm"
                        placeholder="{{ __('Select date range') }}">
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
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-hover mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Invoice #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Vendors') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                            <th class="text-end">{{ __('Paid') }}</th>
                            <th class="text-end">{{ __('Due') }}</th>
                            <th class="text-start">Invoice Created/<br> Updated Date</th>
                            <th>{{ __('Created By') }}</th>
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
                    <h6 class="modal-title text-danger"><i
                            class="bi bi-exclamation-triangle me-1"></i>{{ __('Delete Purchase Invoice') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    {{ __('This will permanently delete the invoice and all payments. Are you sure?') }}</div>
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
    
    <!-- Meta edit modal -->
    <div class="modal fade" id="metaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Edit Invoice Metadata') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="metaForm">
                    <div class="modal-body">
                        <div class="alert alert-danger alert-dissimable" style="display: none;"></div>
                        <input type="hidden" id="metaInvoiceId">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Courier Service') }}</label>
                            <input type="text" id="metaCourier" class="form-control form-control-sm" name="courier_service">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Built Number') }}</label>
                            <input type="text" id="metaBuilt" class="form-control form-control-sm" name="built_number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Received At') }}</label>
                            <input type="text" id="metaReceived" class="form-control form-control-sm" name="received_at" placeholder="YYYY-MM-DD">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            flatpickr('#dateRange', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                allowInput: true
            });

            var table = $('#invoicesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('purchase_invoices.index') }}',
                    data: function(d) {
                        var dr = $('#dateRange').val().split(' to ');
                        d.date_from = dr[0] || '';
                        d.date_to = dr[1] || dr[0] || '';
                        d.status = $('#statusFilter').val();
                        d.type = $('#typeFilter').val();
                    }
                },
				columns: [
					{ data: 'invoice_number', name: 'invoice_number' },
					{ data: 'invoice_date',   name: 'invoice_date' },
					{ data: 'vendor_name',  name: 'vendor_name' },
					{ data: 'type_badge',     name: 'vendor_id',   searchable: false, orderable: false },
					{ data: 'total_amount',   name: 'total_amount',  className: 'text-end' },
					{ data: 'paid_amount',    name: 'paid_amount',   className: 'text-end text-success' },
					{ data: 'remaining',      name: 'remaining',     className: 'text-end', searchable: false },
					{ data: 'updated_at',     name: 'updated_at', render: (val, i, row) => {
						created_at =  row?.created_at;
						updated_at =  val;
						return updated_at != created_at ? `C: ${created_at} <br> U: ${updated_at}` : updated_at;
					}, searchable : true, orderable: false
					},
					{ data: 'created_by',   name: 'created_by'},
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
                language: {
                    processing: '<div class="spinner-border spinner-border-sm text-primary"></div>'
                }
            });

            $('#btnFilter').on('click', function() {
                table.ajax.reload();
            });
            $('#btnReset').on('click', function() {
                $('#dateRange').val('');
                $('#statusFilter').val('');
                $('#typeFilter').val('');
                table.ajax.reload();
            });
            $('#statusFilter, #typeFilter').on('change', function() {
                table.ajax.reload();
            });
            $(document).on('click', '.btn-delete', function() {
                $('#deleteForm').attr('action', $(this).data('url'));
                $('#deleteModal').modal('show');
            });
            
            // Open metadata edit modal
            $(document).on('click', '.btn-edit-meta', function() {
                const id = $(this).data('id');
                $('#metaInvoiceId').val(id);
                $('#metaCourier').val($(this).data('courier') || '');
                $('#metaBuilt').val($(this).data('built') || '');
                $('#metaReceived').val($(this).data('received') || '');
                $('#metaModal').modal('show');
            });

            // simple datepicker for received_at
            flatpickr('#metaReceived', { dateFormat: 'Y-m-d', allowInput: true });

            // submit metadata update via AJAX
            $('#metaForm').on('submit', function(e) {
                e.preventDefault();
                $('#metaModal .alert').html('').hide();
                const id = $('#metaInvoiceId').val();
                let url = "{{ route('purchase_invoices.updateMeta', ':id') }}";
                url = url.replace(':id', id);
                const data = {
                    // _method: 'PUT',
                    courier_service: $('#metaCourier').val(),
                    built_number: $('#metaBuilt').val(),
                    received_at: $('#metaReceived').val(),
                };

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: data,
                    success: function(res) {
                        console.log(res)
                        if (res?.success) {
                            // flash message
                            const msg = res?.message || 'Updated successfully';
                            $('#metaModal .alert').removeClass('alert-danger').addClass('alert-success').html(msg).show();
                            setTimeout(() => {
                                $('#metaModal').modal('hide');
                                table.ajax.reload(null, false);
                            }, 1500);
                        } else {
                            const firstError = Object.values(res.errors)?.[0]?.[0];
                            $('#metaModal .alert').html(firstError).show();
                        }
                    },
                    error: function(xhr) {
                        const err = xhr.responseJSON?.message || 'Failed to update';
                        $('#metaModal .alert').html(err).show();
                    }
                });
            });
        });
    </script>
@endpush
