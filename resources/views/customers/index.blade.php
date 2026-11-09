@extends('layouts.app')
@section('title', __('Customers'))

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ __('Customers') }}</h4>
            <small class="text-muted">{{ __('Manage your regular customers') }}</small>
        </div>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> {{ __('Add Customer') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="customersTable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('City') }}</th>
                        <th>{{ __('Invoices') }}</th>
                        <th>{{ __('Udhar / Due') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6 class="modal-title text-danger"><i
                            class="bi bi-exclamation-triangle me-1"></i>{{ __('Delete Customer') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">{{ __('All customer data will be unlinked.') }}</div>
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
        $(function() {
            $('#customersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('customers.index') }}",
				columns: [
				{ data: 'id',             name: 'id',              width: '50px' },
				{ data: 'name',           name: 'name' },
				{ data: 'phone',          name: 'phone',           defaultContent: '—' },
				{ data: 'city',           name: 'city',            defaultContent: '—' },
				{ data: 'invoices_count', name: 'invoices_count',  searchable: false, className: 'text-center' },
				{ data: 'total_due',      name: 'total_due',       searchable: false, className: 'text-end fw-semibold' },
				{ data: 'status',         name: 'is_active',       searchable: false, className: 'text-center' },
				{ data: 'actions',        name: 'actions',         orderable: false, searchable: false, className: 'text-center' }
				],
				dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex gap-2"Bf><"d-flex align-items-center gap-2"l>>rt<"d-flex justify-content-between mt-3"ip>',
				buttons: [
					{ extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}' },
					{ extend: 'print', className: 'btn btn-sm btn-outline-secondary', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}' }
				],
				pageLength: 15,
				language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
            });

            $(document).on('click', '.btn-delete', function() {
                $('#deleteForm').attr('action', $(this).data('url'));
                $('#deleteModal').modal('show');
            });
        });
    </script>
@endpush
