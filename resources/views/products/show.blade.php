@extends('layouts.app')
@section('title', $product->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ $product->name }}</h4>
    <small class="text-muted">{{ $product->category->name }}</small>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
      <i class="bi bi-pencil me-1"></i> {{ __('Edit') }}
    </a>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
    </a>
  </div>
</div>

<div class="row g-3">

  {{-- Sizes & Prices --}}
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Size, Qeemat aur Stor' : 'Sizes, Prices & Stock' }}</h6>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSizeModal">
          <i class="bi bi-plus-lg me-1"></i> {{ __('Add Size') }}
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="sizesTable">
            <thead class="table-light">
              <tr>
                <th>{{ __('Size') }}</th>
                <th>سائز (اردو)</th>
                <th>{{ __('SKU') }}</th>
                <th class="text-center">{{ __('Stock') }}</th>
                <th class="text-end">{{ app()->getLocale() === 'ur' ? 'Qeemat-e-Kared' : 'Purchase Price' }}</th>
                <th class="text-end">{{ app()->getLocale() === 'ur' ? 'Mojooda Qeemat' : 'Current Price' }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="text-center">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($product->sizes as $size)
              <tr id="size-row-{{ $size->id }}">
                <td class="fw-semibold {{$size?->id}}size-label">{{ $size->size_label }}</td>
                <td class="text-muted {{$size?->id}}size-label_ur" dir="rtl">{{ $size->size_label_ur ?? '—' }}</td>
                <td><code>{{ $size->sku ?? '—' }}</code></td>
                <td class="text-center">
                  <span class="badge {{ $size->isLowStock() ? 'bg-danger' : 'bg-success' }} size-stock" id="stock-{{ $size->id }}">
                    {{ $size->stock_quantity }}
                  </span>
                  @if($size->isLowStock())
                    <i class="bi bi-exclamation-triangle text-warning ms-1" title="{{ app()->getLocale() === 'ur' ? 'Kam Stor!' : 'Low stock!' }}"></i>
                  @endif
                </td>
                <td class="text-end fw-semibold text-primary">
                  PKR {{ number_format($size->currentPrice()?->purchase_price ?? 0, 2) }}
                </td>
                <td class="text-end fw-semibold text-primary" id="price-{{ $size->id }}">
                  PKR {{ number_format($size->currentPrice()?->new_price ?? 0, 2) }}
                </td>
                <td class="text-center">
                  @if($size->is_active)
                    <span class="badge bg-success">{{ app()->getLocale() === 'ur' ? 'Chalaa' : 'Active' }}</span>
                  @else
                    <span class="badge bg-secondary">{{ app()->getLocale() === 'ur' ? 'Band' : 'Inactive' }}</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-info btn-edit-size"
                    data-id="{{ $size->id }}" data-label="{{ $size->size_label }}"
                    data-label_ur="{{ $size->size_label_ur }}"
                    data-active="{{ $size->is_active }}"
                    title="{{ app()->getLocale() === 'ur' ? 'Update Size' : 'Update Size' }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-primary btn-set-price"
                    data-id="{{ $size->id }}" data-label="{{ $size->size_label }}"
                    data-current="{{ $size->currentPrice()?->new_price ?? 0 }}"
                    data-purchase_price="{{ $size->currentPrice()?->purchase_price ?? 0 }}"
                    title="{{ app()->getLocale() === 'ur' ? 'Qeemat Badlo' : 'Update Price' }}">
                    <i class="bi bi-tag"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-success btn-add-stock"
                    data-id="{{ $size->id }}" data-label="{{ $size->size_label }}"
                    data-stock="{{ $size->stock_quantity }}"
                    data-purchase_price="{{ $size->currentPrice()?->purchase_price ?? 0 }}"
                    title="{{ app()->getLocale() === 'ur' ? 'Stor Daalo' : 'Add Stock' }}">
                    <i class="bi bi-plus-circle"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-warning btn-adjust-stock"
                    data-id="{{ $size->id }}" data-label="{{ $size->size_label }}"
                    data-stock="{{ $size->stock_quantity }}"
                    data-purchase_price="{{ $size->currentPrice()?->purchase_price ?? 0 }}"
                    title="{{ app()->getLocale() === 'ur' ? 'Stor Theek Karo' : 'Adjust Stock' }}">
                    <i class="bi bi-sliders"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger btn-delete-size"
                    data-id="{{ $size->id }}" data-label="{{ $size->size_label }}"
                    title="{{ app()->getLocale() === 'ur' ? 'Size Mitao' : 'Delete Size' }}">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">{{ app()->getLocale() === 'ur' ? 'Abhi koi size nahi daala.' : 'No sizes added yet.' }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Price History --}}
    <div class="card mt-3">
      <div class="card-header">
        <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Qeemat ki Tarikh' : 'Price History' }}</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Size') }}</th>
                <th>{{ app()->getLocale() === 'ur' ? 'Qeemat-e-Khared' : 'Purchase Price' }}</th>
                <th>{{ app()->getLocale() === 'ur' ? 'Purani Qeemat' : 'Old Price' }}</th>
                <th>{{ app()->getLocale() === 'ur' ? 'Nai Qeemat' : 'New Price' }}</th>
                <th>{{ app()->getLocale() === 'ur' ? 'Kisne Badla' : 'Changed By' }}</th>
                <th>{{ __('Date') }}</th>
              </tr>
            </thead>
            <tbody>
              @php
                $history = \App\Models\PriceHistory::with(['productSize','changedBy'])
                  ->whereIn('product_size_id', $product->sizes->pluck('id'))
                  ->orderByDesc('changed_at')->take(30)->get();
              @endphp
              @forelse($history as $h)
              <tr>
                <td>{{ $h->productSize->size_label }}</td>
                <td class="text-muted">{{ $h->purchase_price ? 'PKR '.number_format($h->purchase_price,2) : '—' }}</td>
                <td class="text-muted">{{ $h->old_price ? 'PKR '.number_format($h->old_price,2) : '—' }}</td>
                <td class="fw-semibold text-success">PKR {{ number_format($h->new_price,2) }}</td>
                <td>{{ $h->changedBy->name }}</td>
                <td>{{ $h->changed_at->format('d M Y, h:i A') }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted py-3">{{ app()->getLocale() === 'ur' ? 'Abhi koi qeemat ki tarikh nahi.' : 'No price history yet.' }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Product Info --}}
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center">
        @if($product->image)
          <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid rounded mb-3" style="max-height:180px;">
        @else
          <div class="text-muted py-4"><i class="bi bi-image fs-1 d-block mb-1"></i>{{ app()->getLocale() === 'ur' ? 'Koi tasveer nahi' : 'No image' }}</div>
        @endif
        <h5 class="mb-1">{{ $product->name }}</h5>
        @if($product->name_ur)
          <div class="text-muted small" dir="rtl">{{ $product->name_ur }}</div>
        @endif
        <span class="badge bg-info">{{ $product->category->name }}</span>
        @if($product->description)
          <p class="text-muted small mt-2 mb-0">{{ $product->description }}</p>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">{{ app()->getLocale() === 'ur' ? 'Kul Size' : 'Total Sizes' }}</span>
          <strong>{{ $product->sizes->count() }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted small">{{ app()->getLocale() === 'ur' ? 'Kul Stor' : 'Total Stock' }}</span>
          <strong>{{ $product->sizes->sum('stock_quantity') }}</strong>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted small">{{ app()->getLocale() === 'ur' ? 'Kam Stor Cheezein' : 'Low Stock Items' }}</span>
          <strong class="text-danger">{{ $product->sizes->filter(fn($s) => $s->isLowStock())->count() }}</strong>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Set Price Modal --}}
<div class="modal fade" id="editSizeModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-tag me-1"></i> {{ app()->getLocale() === 'ur' ? 'Qeemat Badlo' : 'Update Price' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">{{ __('Size') }}: <strong id="priceSizeLabel"></strong></p>
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Saiz ka Naam' : 'Size Label' }}</label>
        <input type="text" id="label" class="form-control">
        <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'سائز (اردو)' : 'Size (اردو)' }} <span id="currentPriceDisplay"></span></small>
        <input type="text" id="label_ur" class="form-control">
        <div class="form-check form-switch mt-2">
			<input class="form-check-input" type="checkbox" id="isActive" value="1">
			<label class="form-check-label" for="isActive">{{ app()->getLocale() === 'ur' ? 'Maal Chaloo Hai' : 'Product Active' }}</label>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-primary" id="confirmEditSize">{{ app()->getLocale() === 'ur' ? 'Qeemat Badlo' : 'Update Price' }}</button>
      </div>
    </div>
  </div>
</div>
{{-- Set Price Modal --}}
<div class="modal fade" id="setPriceModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-tag me-1"></i> {{ app()->getLocale() === 'ur' ? 'Qeemat Badlo' : 'Update Price' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">{{ __('Size') }}: <strong id="priceSizeLabel"></strong></p>
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Nai Qeemat (PKR)' : 'New Price (PKR)' }}</label>
        <input type="number" id="newPriceInput" class="form-control" step="0.01" min="0" placeholder="0.00">
        <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Mojooda:' : 'Current:' }} <span id="currentPriceDisplay"></span></small>
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Purchase Price (PKR)' : 'Purchase Price (PKR)' }}</label>
        <input type="number" id="purchasePriceInput" class="form-control" step="0.01" min="0" placeholder="0.00">
        <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Ikhtiyari: asal kharid ki qeemat darj karein.' : 'Optional: record the purchase cost if available.' }}</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-primary" id="confirmSetPrice">{{ app()->getLocale() === 'ur' ? 'Qeemat Badlo' : 'Update Price' }}</button>
      </div>
    </div>
  </div>
</div>

{{-- Add Stock Modal --}}
<div class="modal fade" id="addStockModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i> {{ app()->getLocale() === 'ur' ? 'Stor Daalo' : 'Add Stock' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">{{ __('Size') }}: <strong id="addStockLabel"></strong></p>
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Tadadd Daalo' : 'Quantity to Add' }}</label>
        <input type="number" id="addStockQty" class="form-control" min="1" value="1">
        <label class="form-label fw-semibold mt-2">{{ __('Notes') }} ({{ app()->getLocale() === 'ur' ? 'Ikhtiyari' : 'optional' }})</label>
        <input type="text" id="addStockNotes" class="form-control" placeholder="{{ app()->getLocale() === 'ur' ? 'maslan: Naya maal aaya' : 'e.g. New batch received' }}">
        <label class="form-label fw-semibold mt-3">{{ app()->getLocale() === 'ur' ? 'Purchase Price (PKR)' : 'Purchase Price (PKR)' }}</label>
        <input type="number" id="addStockPurchasePrice" class="form-control" step="0.01" min="0" placeholder="0.00">
        <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Ikhtiyari: agar stock kharida gaya hai to qeemat darj karein.' : 'Optional: enter purchase cost if this stock is from a purchase.' }}</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-success" id="confirmAddStock">{{ app()->getLocale() === 'ur' ? 'Stor Daalo' : 'Add Stock' }}</button>
      </div>
    </div>
  </div>
</div>

{{-- Adjust Stock Modal --}}
<div class="modal fade" id="adjustStockModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-sliders me-1"></i> {{ app()->getLocale() === 'ur' ? 'Stor Theek Karo' : 'Adjust Stock' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-1">{{ __('Size') }}: <strong id="adjustStockLabel"></strong></p>
        <p class="text-muted small mb-3">{{ app()->getLocale() === 'ur' ? 'Mojooda Stor:' : 'Current stock:' }} <strong id="adjustCurrentStock"></strong></p>
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Takheeni Tadadd' : 'Set Exact Quantity' }}</label>
        <input type="number" id="adjustStockQty" class="form-control" min="0">
        <label class="form-label fw-semibold mt-2">{{ app()->getLocale() === 'ur' ? 'Waja' : 'Reason' }}</label>
        <input type="text" id="adjustStockNotes" class="form-control" placeholder="{{ app()->getLocale() === 'ur' ? 'maslan: Ginti ki waja se' : 'e.g. Physical count correction' }}">
        <label class="form-label fw-semibold mt-3">{{ app()->getLocale() === 'ur' ? 'Purchase Price (PKR)' : 'Purchase Price (PKR)' }}</label>
        <input type="number" id="adjustPurchasePrice" class="form-control" step="0.01" min="0" placeholder="0.00">
        <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Ikhtiyari: agar adjustment kisi kharid ki wajah se hai to qeemat darj karein.' : 'Optional: record purchase cost if this adjustment is from a purchase.' }}</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-warning" id="confirmAdjustStock">{{ app()->getLocale() === 'ur' ? 'Stor Theek Karo' : 'Adjust Stock' }}</button>
      </div>
    </div>
  </div>
</div>

{{-- Add Size Modal --}}
<div class="modal fade" id="addSizeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">{{ app()->getLocale() === 'ur' ? 'Naya Size Daalo' : 'Add New Size' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ __('Size Label') }} <span class="text-danger">*</span></label>
            <input type="text" id="newSizeLabel" class="form-control" placeholder="e.g. Large / 10-inch">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">سائز اردو میں <small class="text-muted fw-normal">(Urdu Size)</small></label>
            <input type="text" id="newSizeLabelUr" class="form-control" placeholder="مثلاً: بڑا" dir="rtl">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">{{ __('SKU') }}</label>
            <input type="text" id="newSizeSku" class="form-control" placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari' : 'Optional' }}">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Opening Stock') }}</label>
            <input type="number" id="newSizeStock" class="form-control" value="0" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Low Stock Alert') }}</label>
            <input type="number" id="newSizeThreshold" class="form-control" value="5" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Price (PKR)') }} <span class="text-danger">*</span></label>
            <input type="number" id="newSizePrice" class="form-control" placeholder="0.00" step="0.01" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Purchase Price (PKR)' : 'Purchase Price (PKR)' }}</label>
            <input type="number" id="newSizePurchasePrice" class="form-control" placeholder="0.00" min="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-primary" id="confirmAddSize">{{ app()->getLocale() === 'ur' ? 'Size Daalo' : 'Add Size' }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  var activeSizeId = null;

  $(document).on('click', '.btn-edit-size', function () {
    activeSizeId = $(this).data('id');
    $('#priceSizeLabel').text($(this).data('label'));
	$('#label').val($(this).data('label'))
	$('#label_ur').val($(this).data('label_ur')),
	$('#isActive').attr('checked', $(this).data('active'))
    $('#editSizeModal').modal('show');
  });

  $('#confirmEditSize').on('click', function () {
    var label = $('#label').val()?.trim();
    if (!label || label == '') { alert('{{ app()->getLocale() === 'ur' ? 'Sahi label likhein.' : 'Enter a valid label.' }}'); return; }

    $.post('/sizes/' + activeSizeId , {
	  _method: 'PATCH',
	  _token: $('meta[name="csrf-token"]').attr('content'),
      size_label: $('#label').val(),
      size_label_ur: $('#label_ur').val(),
      is_active: $('#isActive').prop('checked') == true ? 1 : 0
    })
      .done(function () {
        $(`#${activeSizeId}size-label`).text(label);
        $(`#${activeSizeId}size-label_ur`).text(label_ur);
        $('#editSizeModal').modal('hide');
        showToast('{{ app()->getLocale() === 'ur' ? 'Qeemat update ho gayi!' : 'Price updated successfully!' }}', 'success');
      })
      .fail(function () { alert('{{ app()->getLocale() === 'ur' ? 'label update nahi ho saki.' : 'Failed to update label.' }}'); });
  });

  $(document).on('click', '.btn-set-price', function () {
    activeSizeId = $(this).data('id');
    $('#priceSizeLabel').text($(this).data('label'));
    $('#currentPriceDisplay').text('PKR ' + parseFloat($(this).data('current')).toFixed(2));
    $('#newPriceInput').val('').focus();
    $('#purchasePriceInput').val(parseFloat($(this).data('purchase_price')).toFixed(2) ?? 0);
    $('#setPriceModal').modal('show');
  });

  $('#confirmSetPrice').on('click', function () {
    var price = $('#newPriceInput').val();
    if (!price || price <= 0) { alert('{{ app()->getLocale() === 'ur' ? 'Sahi qeemat likhein.' : 'Enter a valid price.' }}'); return; }

    $.post('/sizes/' + activeSizeId + '/set-price', {
      price: price,
      purchase_price: $('#purchasePriceInput').val() || null
    })
      .done(function () {
        $('#price-' + activeSizeId).text('PKR ' + parseFloat(price).toFixed(2));
        $('#setPriceModal').modal('hide');
        showToast('{{ app()->getLocale() === 'ur' ? 'Qeemat update ho gayi!' : 'Price updated successfully!' }}', 'success');
      })
      .fail(function () { alert('{{ app()->getLocale() === 'ur' ? 'Qeemat update nahi ho saki.' : 'Failed to update price.' }}'); });
  });

  $(document).on('click', '.btn-add-stock', function () {
    activeSizeId = $(this).data('id');
    $('#addStockLabel').text($(this).data('label'));
    $('#addStockQty').val(1);
    $('#addStockNotes').val('');
    $('#addStockPurchasePrice').val(parseFloat($(this).data('purchase_price')).toFixed(2) ?? 0);
    $('#addStockModal').modal('show');
  });

  $('#confirmAddStock').on('click', function () {
    var qty = parseInt($('#addStockQty').val());
    if (!qty || qty < 1) { alert('{{ app()->getLocale() === 'ur' ? 'Sahi tadadd likhein.' : 'Enter a valid quantity.' }}'); return; }

    $.post('/sizes/' + activeSizeId + '/add-stock', {
      quantity: qty,
      notes: $('#addStockNotes').val(),
      purchase_price: $('#addStockPurchasePrice').val() || null
    }).done(function (res) {
      var badge = $('#stock-' + activeSizeId);
      badge.text(res.new_stock);
      badge.removeClass('bg-danger bg-success')
           .addClass(res.is_low ? 'bg-danger' : 'bg-success');
      $('#addStockModal').modal('hide');
      showToast('{{ app()->getLocale() === 'ur' ? 'Stor update ho gaya!' : 'Stock updated!' }}', 'success');
    }).fail(function () { alert('{{ app()->getLocale() === 'ur' ? 'Stor update nahi ho saka.' : 'Failed to update stock.' }}'); });
  });

  $(document).on('click', '.btn-adjust-stock', function () {
    activeSizeId = $(this).data('id');
    $('#adjustStockLabel').text($(this).data('label'));
    $('#adjustCurrentStock').text($(this).data('stock'));
    $('#adjustStockQty').val($(this).data('stock'));
    $('#adjustStockNotes').val('');
    $('#adjustPurchasePrice').val(parseFloat($(this).data('purchase_price')).toFixed(2) ?? 0);
    $('#adjustStockModal').modal('show');
  });

  $('#confirmAdjustStock').on('click', function () {
    var qty = parseInt($('#adjustStockQty').val());
    if (isNaN(qty) || qty < 0) { alert('{{ app()->getLocale() === 'ur' ? 'Sahi tadadd likhein.' : 'Enter a valid quantity.' }}'); return; }

    $.post('/sizes/' + activeSizeId + '/adjust-stock', {
      quantity: qty,
      notes: $('#adjustStockNotes').val(),
      purchase_price: $('#adjustPurchasePrice').val() || null
    }).done(function (res) {
      var badge = $('#stock-' + activeSizeId);
      badge.text(res.new_stock);
      badge.removeClass('bg-danger bg-success')
           .addClass(res.is_low ? 'bg-danger' : 'bg-success');
      $('#adjustCurrentStock').text(res.new_stock);
      $('#adjustStockModal').modal('hide');
      showToast('{{ app()->getLocale() === 'ur' ? 'Stor theek ho gaya!' : 'Stock adjusted!' }}', 'success');
    }).fail(function () { alert('{{ app()->getLocale() === 'ur' ? 'Stor theek nahi ho saka.' : 'Failed to adjust stock.' }}'); });
  });

  $(document).on('click', '.btn-delete-size', function () {
    activeSizeId = $(this).data('id');
    let decision = window.confirm(`Are you sure you want to delete` );
    if (decision) {
      $.ajax({
        url : `/sizes/${activeSizeId}`,
        method: 'DELETE',
        success : (res)=> {
          console.log(res);
          if (res?.success) {
            showToast('{{ app()->getLocale() === 'ur' ? 'Size mit gya!' : 'Size Deleted!' }}', 'success');
            setTimeout(() => {
              location.reload();
            }, 1500);
            return
          } else
          alert(res?.message);
        },
        error : (err) => {
          console.log(err);
          alert('Operation Failed');
        },

      })
    }
  });

  // $('#confirmDeleteSize').on('click', function () {
  //   var qty = parseInt($('#adjustStockQty').val());
  //   if (isNaN(qty) || qty < 0) { alert('{{ app()->getLocale() === 'ur' ? 'Sahi tadadd likhein.' : 'Enter a valid quantity.' }}'); return; }

  //   $.post('/sizes/' + activeSizeId, {
  //   // $.post('/sizes/' + activeSizeId + '/adjust-stock', {
  //     quantity: qty,
  //     notes: $('#adjustStockNotes').val(),
  //     purchase_price: $('#adjustPurchasePrice').val() || null
  //   }).done(function (res) {
  //     var badge = $('#stock-' + activeSizeId);
  //     badge.text(res.new_stock);
  //     badge.removeClass('bg-danger bg-success')
  //          .addClass(res.is_low ? 'bg-danger' : 'bg-success');
  //     $('#adjustCurrentStock').text(res.new_stock);
  //     $('#adjustStockModal').modal('hide');
  //     showToast('{{ app()->getLocale() === 'ur' ? 'Stor theek ho gaya!' : 'Stock adjusted!' }}', 'success');
  //   }).fail(function () { alert('{{ app()->getLocale() === 'ur' ? 'Stor theek nahi ho saka.' : 'Failed to adjust stock.' }}'); });
  // });

  $('#confirmAddSize').on('click', function () {
    var data = {
      size_label:          $.trim($('#newSizeLabel').val()),
      size_label_ur:       $.trim($('#newSizeLabelUr').val()),
      sku:                 $.trim($('#newSizeSku').val()),
      stock_quantity:      $('#newSizeStock').val(),
      low_stock_threshold: $('#newSizeThreshold').val(),
      initial_price:       $('#newSizePrice').val(),
      purchase_price:      $('#newSizePurchasePrice').val() || null
    };
    if (!data.size_label || !data.initial_price) {
      alert('{{ app()->getLocale() === 'ur' ? 'Size ka naam aur qeemat zaroori hain.' : 'Size label and price are required.' }}');
      return;
    }

    $.post('{{ route('products.sizes.store', $product) }}', data)
      .done(function () {
        $('#addSizeModal').modal('hide');
        location.reload();
      })
      .fail(function (xhr) {
        alert(xhr.responseJSON?.message ?? '{{ app()->getLocale() === 'ur' ? 'Size nahi daala ja saka.' : 'Failed to add size.' }}');
      });
  });

  function showToast(msg, type) {
    var bg = type === 'success' ? 'bg-success' : 'bg-danger';
    var toast = $('<div class="toast align-items-center text-white ' + bg + ' border-0 position-fixed bottom-0 end-0 m-3" role="alert" style="z-index:9999">' +
      '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
    $('body').append(toast);
    var bsToast = new bootstrap.Toast(toast[0], { delay: 3000 });
    bsToast.show();
    toast.on('hidden.bs.toast', function () { toast.remove(); });
  }
});
</script>
@endpush
