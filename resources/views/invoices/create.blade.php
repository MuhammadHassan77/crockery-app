@extends('layouts.app')
@section('title', __('New Invoice'))

@push('styles')
<style>
  .item-row td { vertical-align: middle; }
  .item-row .remove-item { opacity: .4; }
  .item-row:hover .remove-item { opacity: 1; }
  #totalsBox { background: var(--card-bg, #fff); }
  .customer-type-btn.active { box-shadow: 0 0 0 2px var(--bs-primary); }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('New Invoice') }}</h4>
    <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Maal aur Adaigi ki Malumat Bharein' : 'Fill in items and payment details' }}</small>
  </div>
  <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
  </a>
</div>

<form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
@csrf
<div class="row g-3">

  {{-- LEFT: Customer + Items --}}
  <div class="col-lg-8">

    {{-- Customer Section --}}
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center gap-3">
        <h6 class="mb-0 me-3">{{ __('Customer') }}</h6>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-primary active customer-type-btn" data-type="walkin">
            <i class="bi bi-person me-1"></i> {{ __('Walk-in') }}
          </button>
          <button type="button" class="btn btn-outline-primary customer-type-btn" data-type="regular">
            <i class="bi bi-people me-1"></i> {{ app()->getLocale() === 'ur' ? 'Pakka Grahak' : 'Regular Customer' }}
          </button>
        </div>
      </div>
      <div class="card-body">

        {{-- Walk-in fields --}}
        <div id="walkinFields">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ app()->getLocale() === 'ur' ? 'Naam (Ikhtiyari)' : 'Name (optional)' }}</label>
              <input type="text" name="walk_in_name" class="form-control form-control-sm"
                     placeholder="{{ app()->getLocale() === 'ur' ? 'Seedha aane wale ka naam' : 'Walk-in customer name' }}" value="{{ old('walk_in_name') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ app()->getLocale() === 'ur' ? 'Phone (Ikhtiyari)' : 'Phone (optional)' }}</label>
              <input type="text" name="walk_in_phone" class="form-control form-control-sm"
                     placeholder="03xx-xxxxxxx" value="{{ old('walk_in_phone') }}">
            </div>
          </div>
        </div>

        {{-- Regular customer search --}}
        <div id="regularFields" style="display:none;">
          <input type="hidden" name="customer_id" id="customerId" value="{{ old('customer_id', request('customer_id')) }}">
          <div class="position-relative">
            <input type="text" id="customerSearch" class="form-control form-control-sm"
                   placeholder="{{ app()->getLocale() === 'ur' ? 'Naam ya phone se dhoondein...' : 'Search by name or phone...' }}" autocomplete="off">
            <div id="customerDropdown"
                 class="position-absolute w-100 bg-white border rounded shadow-sm mt-1"
                 style="z-index:999; display:none; max-height:220px; overflow-y:auto;">
            </div>
          </div>
          <div id="selectedCustomer" class="mt-2" style="display:none;">
            <div class="alert alert-info py-2 mb-0 d-flex align-items-center justify-content-between">
              <div>
                <i class="bi bi-person-check me-1"></i>
                <strong id="selCustomerName"></strong>
                <span class="text-muted ms-2 small" id="selCustomerPhone"></span>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="clearCustomer">
                <i class="bi bi-x"></i>
              </button>
            </div>
            {{-- Previous balance alert --}}
            <div id="prevBalanceAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 d-flex justify-content-between align-items-center" style="display:none!important;">
              <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span class="fw-semibold small">{{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi:' : 'Previous Outstanding:' }}</span>
              </div>
              <strong id="prevBalanceAmount" class="fs-6">PKR 0</strong>
            </div>
          </div>
          <div class="mt-2">
            <a href="{{ route('customers.create') }}" target="_blank" class="small">
              <i class="bi bi-plus-circle me-1"></i>{{ app()->getLocale() === 'ur' ? 'Naya grahak daalo' : 'Add new customer' }}
            </a>
          </div>
        </div>

      </div>
    </div>

    {{-- Invoice Meta --}}
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">{{ app()->getLocale() === 'ur' ? 'Raseed ki Tarikh' : 'Invoice Date' }}</label>
            <input type="date" name="invoice_date" class="form-control form-control-sm"
                   value="{{ old('invoice_date', now()->toDateString()) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">{{ app()->getLocale() === 'ur' ? 'Maturity Tarikh (Ikhtiyari)' : 'Due Date (optional)' }}</label>
            <input type="date" name="due_date" class="form-control form-control-sm"
                   value="{{ old('due_date') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">{{ __('Notes') }}</label>
            <input type="text" name="notes" class="form-control form-control-sm"
                   placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari note...' : 'Optional note...' }}" value="{{ old('notes') }}">
          </div>
        </div>
      </div>
    </div>

    {{-- Items --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Ashtya' : 'Items' }}</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
          <i class="bi bi-plus-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Maal Daalo' : 'Add Item' }}
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" id="itemsTable">
            <thead class="table-light">
              <tr>
                <th style="min-width:200px">{{ app()->getLocale() === 'ur' ? 'Maal / Size' : 'Product / Size' }}</th>
                <th style="width:90px">{{ app()->getLocale() === 'ur' ? 'Tadadd' : 'Qty' }}</th>
                <th style="width:150px">{{ app()->getLocale() === 'ur' ? 'Ikaayi Qeemat' : 'Unit Price' }}</th>
                <th style="width:150px">{{ __('Total') }}</th>
                <th style="width:44px"></th>
              </tr>
            </thead>
            <tbody id="itemRows">
              {{-- rows injected by JS --}}
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        {{ app()->getLocale() === 'ur' ? 'Maal/size chunein — qeemat khud aa jaegi.' : 'Select a product/size — price fills automatically from current price.' }}
      </div>
    </div>

  </div>

  {{-- RIGHT: Totals + Payment --}}
  <div class="col-lg-4">

    {{-- Totals --}}
    <div class="card mb-3" id="totalsBox">
      <div class="card-header"><h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Khulasa' : 'Summary' }}</h6></div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Upar Kul' : 'Subtotal' }}</span>
          <strong id="subtotalDisplay">PKR 0</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted">{{ __('Discount') }}</span>
          <div class="input-group input-group-sm" style="width:110px;">
            <span class="input-group-text">PKR</span>
            <input type="number" name="discount_amount" id="discountInput"
                   class="form-control text-end" value="{{ old('discount_amount', 0) }}"
                   min="0" step="1">
          </div>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between mb-3">
          <span class="fw-bold">{{ __('Total') }}</span>
          <strong class="fs-5 text-primary" id="totalDisplay">PKR 0</strong>
        </div>

        <input type="hidden" name="subtotal" id="subtotalHidden">
        <input type="hidden" name="tax_amount" value="0">

        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Abhi Adaigi Li Gayi' : 'Payment Received Now' }}</label>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="bi bi-cash"></i></span>
          <input type="number" name="initial_payment" id="initialPayment"
                 class="form-control" value="{{ old('initial_payment', 0) }}"
                 min="0" step="1" placeholder="0">
        </div>

        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Adaigi ka Tariqa' : 'Payment Method' }}</label>
        <select name="payment_method" class="form-select form-select-sm mb-3">
          <option value="cash">{{ app()->getLocale() === 'ur' ? 'Naqdh' : 'Cash' }}</option>
          <option value="jazzcash">JazzCash</option>
          <option value="easypaisa">EasyPaisa</option>
          <option value="bank">{{ app()->getLocale() === 'ur' ? 'Bank Tabadla' : 'Bank Transfer' }}</option>
          <option value="other">{{ app()->getLocale() === 'ur' ? 'Aur' : 'Other' }}</option>
        </select>

        {{-- Previous Balance row (hidden until regular customer with balance selected) --}}
        <div id="summaryPrevBalanceRow" class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="display:none!important;background:#fff3cd;border:1px solid #ffc107;">
          <span class="small fw-semibold">
            <i class="bi bi-clock-history me-1"></i>
            {{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi' : 'Prev. Balance' }}
          </span>
          <strong id="summaryPrevBalance" class="text-danger">PKR 0</strong>
        </div>

        <div class="alert py-2 mb-0" id="statusPreview">
          <div class="d-flex justify-content-between">
            <span class="small">{{ app()->getLocale() === 'ur' ? 'Is Bill ka Baqi' : 'This Bill Due' }}</span>
            <strong id="remainingDisplay">PKR 0</strong>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="small">{{ app()->getLocale() === 'ur' ? 'Raseed ki Halat' : 'Invoice Status' }}</span>
            <span id="statusBadge" class="badge bg-secondary">{{ __('Pending') }}</span>
          </div>
          <div id="totalOutstandingRow" class="d-flex justify-content-between mt-2 pt-2 border-top" style="display:none!important;">
            <span class="small fw-bold">{{ app()->getLocale() === 'ur' ? 'Kul Baqi (Sab Milaa kar)' : 'Total Outstanding' }}</span>
            <strong id="totalOutstandingDisplay" class="text-danger">PKR 0</strong>
          </div>
        </div>
      </div>
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="submitBtn">
      <i class="bi bi-check-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Raseed Banao' : 'Generate Invoice' }}
    </button>
    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100 mt-2">{{ __('Cancel') }}</a>

  </div>
</div>
</form>
@endsection

@push('scripts')
<script>
$(function () {

  var productSizes = {!! json_encode($products->map(function($s) {
    return [
      'id'       => $s->id,
      'label'    => $s->product->name . ' — ' . $s->size_label,
      'name'     => $s->product->name,
      'name_ur'  => $s->product->name_ur ?? '',
      'label_ur' => $s->size_label_ur ?? '',
      'price'    => optional($s->currentPrice())->new_price ?? 0,
      'stock'    => $s->stock_quantity,
    ];
  })->values()) !!};

  var rowIndex = 0;

  function buildSizeOptions(selectedId) {
    var opts = '<option value="">{{ app()->getLocale() === 'ur' ? '-- Maal/Size Chunein --' : '-- Select Product/Size --' }}</option>';
    $.each(productSizes, function (i, s) {
      var sel = (s.id == selectedId) ? 'selected' : '';
      opts += '<option value="' + s.id
            + '" data-price="' + s.price
            + '" data-stock="' + s.stock
            + '" data-name="' + $('<div>').text(s.name).html()
            + '" data-name-ur="' + $('<div>').text(s.name_ur).html()
            + '" data-label-ur="' + $('<div>').text(s.label_ur).html()
            + '" ' + sel + '>'
            + $('<div>').text(s.label).html()
            + '</option>';
    });
    return opts;
  }

  function addItemRow(sizeId, qty, price) {
    sizeId = sizeId || '';
    qty    = qty    || 1;
    price  = price  || '';
    var i  = rowIndex++;
    var row = `
      <tr class="item-row" data-index="${i}">
        <td>
          <select name="items[${i}][product_size_id]" class="form-select form-select-sm size-select" required>
            ${buildSizeOptions(sizeId)}
          </select>
          <input type="hidden" name="items[${i}][product_name]"    class="item-product-name">
          <input type="hidden" name="items[${i}][product_name_ur]" class="item-product-name-ur">
          <input type="hidden" name="items[${i}][size_label]"      class="item-size-label">
          <input type="hidden" name="items[${i}][size_label_ur]"   class="item-size-label-ur">
          <div class="text-muted small mt-1 item-stock-info"></div>
        </td>
        <td>
          <input type="number" name="items[${i}][quantity]"
                 class="form-control form-control-sm item-qty text-center"
                 value="${qty}" min="1" required>
        </td>
        <td>
          <div class="input-group input-group-sm">
            <span class="input-group-text">PKR</span>
            <input type="number" name="items[${i}][unit_price]"
                   class="form-control form-control-sm item-price text-end"
                   value="${price}" min="0" step="0.01" required>
          </div>
        </td>
        <td class="text-end fw-semibold item-total text-primary">PKR 0</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove-item">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>`;
    $('#itemRows').append(row);

    if (sizeId) {
      var $row = $('#itemRows .item-row:last');
      var $opt = $row.find('.size-select option:selected');
      populateSizeFields($row, $opt);
    }

    recalculate();
  }

  function populateSizeFields($row, $opt) {
    var label   = $opt.text().split(' — ');
    var nameEn  = $opt.data('name')    || label[0] || $opt.text();
    var nameUr  = $opt.data('name-ur') || '';
    var labelUr = $opt.data('label-ur') || '';
    $row.find('.item-product-name').val(nameEn);
    $row.find('.item-product-name-ur').val(nameUr);
    $row.find('.item-size-label').val(label[1] || '');
    $row.find('.item-size-label-ur').val(labelUr);
    var price = $opt.data('price') || 0;
    var stock = $opt.data('stock') || 0;
    if (price > 0 && !$row.find('.item-price').val()) {
      $row.find('.item-price').val(price);
    }
    $row.find('.item-stock-info').html(
      stock > 0
        ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + stock + ' {{ app()->getLocale() === 'ur' ? 'Stor mein' : 'in stock' }}</span>'
        : '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>{{ app()->getLocale() === 'ur' ? 'Khatam' : 'Out of stock' }}</span>'
    );
  }

  addItemRow();

  $('#addItemBtn').on('click', function () { addItemRow(); });

  $(document).on('click', '.remove-item', function () {
    if ($('.item-row').length <= 1) {
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek maal zaroori hai.' : 'At least one item is required.' }}');
      return;
    }
    $(this).closest('tr').remove();
    recalculate();
  });

  $(document).on('change', '.size-select', function () {
    var $row = $(this).closest('tr');
    var $opt = $(this).find('option:selected');
    $row.find('.item-price').val($opt.data('price') || '');
    populateSizeFields($row, $opt);
    recalculate();
  });

  $(document).on('input', '.item-qty, .item-price, #discountInput, #initialPayment', recalculate);

  function recalculate() {
    var subtotal = 0;
    $('.item-row').each(function () {
      var qty   = parseFloat($(this).find('.item-qty').val())   || 0;
      var price = parseFloat($(this).find('.item-price').val()) || 0;
      var line  = qty * price;
      $(this).find('.item-total').text('PKR ' + number(line));
      subtotal += line;
    });

    var discount  = parseFloat($('#discountInput').val())   || 0;
    var total     = Math.max(0, subtotal - discount);
    var paid      = parseFloat($('#initialPayment').val())  || 0;
    var remaining = Math.max(0, total - paid);

    $('#subtotalDisplay').text('PKR ' + number(subtotal));
    $('#totalDisplay').text('PKR ' + number(total));
    $('#subtotalHidden').val(subtotal.toFixed(2));
    $('#remainingDisplay').text('PKR ' + number(remaining));

    var badge = '', cls = '';
    if (paid <= 0)          { badge = '{{ __('Pending') }}';   cls = 'bg-danger'; }
    else if (paid >= total) { badge = '{{ __('Paid') }}';       cls = 'bg-success'; }
    else                    { badge = '{{ __('Semi-Paid') }}';  cls = 'bg-warning text-dark'; }
    $('#statusBadge').attr('class', 'badge ' + cls).text(badge);

    // Show total outstanding = this bill's remaining + customer's previous balance
    var prevBal = window._prevBalance || 0;
    if (prevBal > 0) {
      var totalOutstanding = remaining + prevBal;
      $('#totalOutstandingDisplay').text('PKR ' + number(totalOutstanding));
      $('#totalOutstandingRow').show();
    } else {
      $('#totalOutstandingRow').hide();
    }

    if (paid > total) {
      $('#initialPayment').val(total.toFixed(0));
      recalculate();
    }
  }

  function number(n) {
    return parseFloat(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  $('.customer-type-btn').on('click', function () {
    $('.customer-type-btn').removeClass('active');
    $(this).addClass('active');
    var type = $(this).data('type');
    if (type === 'walkin') {
      $('#walkinFields').show();
      $('#regularFields').hide();
      $('input[name="customer_id"]').val('');
    } else {
      $('#walkinFields').hide();
      $('#regularFields').show();
    }
  });

  @if(request('customer_id'))
    $('[data-type="regular"]').trigger('click');
    $.getJSON('{{ route('customers.show', ':id') }}'.replace(':id', {{ (int) request('customer_id') }}), function (data) {
      // not JSON — skip
    });
  @endif

  var searchTimer;
  $('#customerSearch').on('input', function () {
    clearTimeout(searchTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#customerDropdown').hide(); return; }
    searchTimer = setTimeout(function () {
      $.getJSON('{{ route('customers.index') }}', {
        ajax: 1, search: { value: q }, length: 10, start: 0,
        columns: [
          {data:'id', searchable:true},
          {data:'name', searchable:true},
          {data:'phone', searchable:true},
          {data:'city', searchable:true}
        ]
      }, function (res) {
        var html = '';
        if (res.data && res.data.length) {
          $.each(res.data, function (i, c) {
            html += '<div class="customer-option px-3 py-2 border-bottom" style="cursor:pointer;" '
                  + 'data-id="' + c.id + '" data-name="' + $('<div>').text(c.name).html() + '" '
                  + 'data-phone="' + (c.phone || '') + '">'
                  + '<strong>' + $('<div>').text(c.name).html() + '</strong>'
                  + (c.phone ? '<span class="text-muted ms-2 small">' + c.phone + '</span>' : '')
                  + (c.city  ? '<span class="text-muted ms-2 small">— ' + c.city + '</span>' : '')
                  + '</div>';
          });
        } else {
          html = '<div class="px-3 py-2 text-muted small">{{ app()->getLocale() === 'ur' ? 'Koi grahak nahi mila.' : 'No customers found.' }}</div>';
        }
        $('#customerDropdown').html(html).show();
      });
    }, 300);
  });

  $(document).on('click', '.customer-option', function () {
    var id    = $(this).data('id');
    var name  = $(this).data('name');
    var phone = $(this).data('phone');

    $('#customerId').val(id);
    $('#selCustomerName').text(name);
    $('#selCustomerPhone').text(phone ? '(' + phone + ')' : '');
    $('#selectedCustomer').show();
    $('#customerSearch').val('').hide();
    $('#customerDropdown').hide();

    // Fetch previous outstanding balance for this customer
    $('#prevBalanceAlert').hide();
    $.getJSON('{{ url('customers') }}/' + id + '/balance', function (res) {
      if (res.has_balance) {
        $('#prevBalanceAmount').text(res.formatted);
        $('#prevBalanceAlert').show();
        // Also update the summary "Previous Balance" row
        $('#summaryPrevBalance').text(res.formatted);
        $('#summaryPrevBalanceRow').show();
        window._prevBalance = res.balance;
      } else {
        $('#prevBalanceAlert').hide();
        $('#summaryPrevBalanceRow').hide();
        window._prevBalance = 0;
      }
    });
  });

  $('#clearCustomer').on('click', function () {
    $('#customerId').val('');
    $('#selectedCustomer').hide();
    $('#prevBalanceAlert').hide();
    $('#summaryPrevBalanceRow').hide();
    window._prevBalance = 0;
    $('#customerSearch').val('').show().focus();
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#regularFields').length) {
      $('#customerDropdown').hide();
    }
  });

  $('#invoiceForm').on('submit', function (e) {
    var hasItem = false;
    $('.item-row').each(function () {
      if ($(this).find('.size-select').val()) hasItem = true;
    });
    if (!hasItem) {
      e.preventDefault();
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek maal daalo.' : 'Please add at least one item to the invoice.' }}');
      return false;
    }
    $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ app()->getLocale() === 'ur' ? 'Mehfooz...' : 'Saving...' }}');
  });

});
</script>
@endpush
