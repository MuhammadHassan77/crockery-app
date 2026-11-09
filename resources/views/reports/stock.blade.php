@extends('layouts.app')
@section('title', __('Stock Report'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Stock Report') }}</h4>
    <small class="text-muted">{{ __('Current inventory levels') }}</small>
  </div>
  <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
    <i class="bi bi-printer me-1"></i> {{ __('Print') }}
  </button>
</div>

@if($lowStock->count() > 0)
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3 no-print">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <div>
    <strong>{{ $lowStock->count() }}</strong>
    {{ app()->getLocale() === 'ur' ? 'cheez(ein) kam maal ki hadd par hain.' : 'item(s) are at or below low-stock threshold.' }}
  </div>
</div>
@endif

@php
  $totalItems    = $sizes->count();
  $totalStock    = $sizes->sum('stock_quantity');
  $lowStockCount = $lowStock->count();
  $outOfStock    = $sizes->where('stock_quantity', 0)->count();
@endphp

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total SKUs') }}</div>
      <div class="fs-4 fw-bold">{{ $totalItems }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Total Units') }}</div>
      <div class="fs-4 fw-bold">{{ number_format($totalStock) }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Low Stock') }}</div>
      <div class="fs-4 fw-bold text-warning">{{ $lowStockCount }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="text-muted small">{{ __('Out of Stock') }}</div>
      <div class="fs-4 fw-bold text-danger">{{ $outOfStock }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0">{{ __('Inventory Levels') }}</h6></div>
  <div class="card-body p-2">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="stockTable">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Size') }}</th>
            <th class="text-center">{{ __('Stock Quantity') }}</th>
            <th class="text-center">{{ __('Min Stock') }}</th>
            <th>{{ __('Current Price') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sizes as $i => $size)
          @php
            $isLow  = $size->isLowStock();
            $isOut  = $size->stock_quantity <= 0;
            $price  = $size->currentPrice()?->new_price;
          @endphp
          <tr class="{{ $isOut ? 'table-danger' : ($isLow ? 'table-warning' : '') }}">
            <td class="text-muted small">{{ $i + 1 }}</td>
            <td>
              <a href="{{ route('products.show', $size->product) }}" class="fw-semibold">
                {{ $size->product->name }}
              </a>
            </td>
            <td>{{ $size->product->category?->name ?? '—' }}</td>
            <td>{{ $size->size_label }}</td>
            <td class="text-center fw-semibold {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : '') }}">
              {{ $size->stock_quantity }}
            </td>
            <td class="text-center text-muted">{{ $size->low_stock_threshold }}</td>
            <td>{{ $price ? 'PKR ' . number_format($price, 0) : '—' }}</td>
            <td>
              @if($isOut)
                <span class="badge bg-danger">{{ __('Out of Stock') }}</span>
              @elseif($isLow)
                <span class="badge bg-warning text-dark">{{ __('Low Stock') }}</span>
              @else
                <span class="badge bg-success">{{ app()->getLocale() === 'ur' ? 'Theek Hai' : 'OK' }}</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $('#stockTable').DataTable({
    paging: true, searching: true, info: true,
    dom: '<"d-flex justify-content-between mb-2"<"d-flex gap-2"Bl>f>tip',
    buttons: [
      { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: '<i class="bi bi-file-earmark-excel me-1"></i>{{ __("Excel") }}' },
      { extend: 'csv',   className: 'btn btn-sm btn-outline-info',    text: '<i class="bi bi-filetype-csv me-1"></i>{{ __("CSV") }}' },
      { extend: 'print', className: 'btn btn-sm btn-outline-secondary no-print', text: '<i class="bi bi-printer me-1"></i>{{ __("Print") }}' }
    ],
    columnDefs: [{ targets: [4], type: 'num' }]
  });
});
</script>
@endpush
