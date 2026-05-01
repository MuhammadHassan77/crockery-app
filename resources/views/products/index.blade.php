@extends('layouts.app')
@section('title', 'Products')

@push('styles')
<style>
  .category-badge { font-size: .75rem; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Products</h4>
    <small class="text-muted">Manage your crockery product catalog</small>
  </div>
  <a href="{{ route('products.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Add Product
  </a>
</div>

{{-- Quick category filter --}}
<div class="card mb-3">
  <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small me-1">Filter by category:</span>
    <button class="btn btn-sm btn-primary category-filter" data-id="">All</button>
    @foreach($categories as $cat)
      <button class="btn btn-sm btn-outline-secondary category-filter" data-id="{{ $cat->id }}">
        {{ $cat->name }}
      </button>
    @endforeach
  </div>
</div>

<div class="card">
  <div class="card-body">
    <table id="productsTable" class="table table-hover w-100">
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Category</th>
          <th>Sizes</th>
          <th>Total Stock</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- Delete Confirm Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete Product</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-0">Are you sure? This cannot be undone.</div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteForm" method="POST">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  var activeCategoryId = '';

  var table = $('#productsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route('products.index') }}',
      data: function (d) {
        d.category_id = activeCategoryId;
      }
    },
    columns: [
      { data: 'id',         name: 'id',         width: '50px' },
      { data: 'name',       name: 'name' },
      { data: 'category',   name: 'categories.name' },
      { data: 'sizes_count',name: 'sizes_count', searchable: false, className: 'text-center' },
      { data: 'stock',      name: 'stock',       searchable: false, className: 'text-center' },
      { data: 'status',     name: 'is_active',   searchable: false, className: 'text-center' },
      { data: 'actions',    name: 'actions',     orderable: false,  searchable: false, className: 'text-center' }
    ],
    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex gap-2"Bf><"d-flex align-items-center gap-2"l>>rt<"d-flex justify-content-between mt-3"ip>',
    buttons: [
      { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel' },
      { extend: 'print', className: 'btn btn-sm btn-outline-secondary', text: '<i class="bi bi-printer me-1"></i>Print' }
    ],
    pageLength: 15,
    language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
  });

  // Category filter buttons
  $(document).on('click', '.category-filter', function () {
    $('.category-filter').removeClass('btn-primary').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
    activeCategoryId = $(this).data('id');
    table.ajax.reload();
  });

  // Delete modal
  $(document).on('click', '.btn-delete', function () {
    $('#deleteForm').attr('action', $(this).data('url'));
    $('#deleteModal').modal('show');
  });
});
</script>
@endpush
