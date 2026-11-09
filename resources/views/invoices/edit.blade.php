@extends('layouts.app')
@section('title', __('Edit Invoice'))

@push('styles')
<style>
  .is-invalid,
  .is-invalid.form-control,
  .is-invalid.form-select,
  .select2-container--default .select2-selection--single.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
  }
  .field-error-text {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #dc3545;
  }
</style>
@endpush
@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Edit Invoice') }} — {{ $invoice->invoice_number }}</h4>
    <small class="text-muted">{{ __('Modify invoice details (items cannot be changed here).') }}</small>
  </div>
  <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
  </a>
</div>

<form action="{{ route('invoices.update', $invoice) }}" method="POST" id="invoiceForm">
  @csrf
  @method('PUT')
  <div class="alert" id="alertMsg"></div>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Customer') }}</h6></div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Customer (optional)') }}</label>
              <select name="customer_id" id="customerSelect" class="form-select form-select-sm select2-remote" 
                      data-url="{{ route('api.search.customers') }}"
                      data-locale="{{ app()->getLocale() }}"
                      placeholder="{{ app()->getLocale() === 'ur' ? 'Naam ya phone se dhoondein...' : 'Search by name or phone...' }}">
                <option value="">-- {{ __('Walk-in / none') }} --</option>
                @if($invoice->customer_id)
                  <option value="{{ $invoice->customer_id }}" selected>{{ $invoice->customer->name }} ({{ $invoice->customer->phone ?? '' }})</option>
                @endif
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Walk-in name (optional)') }}</label>
              <input type="text" name="walk_in_name" class="form-control form-control-sm" value="{{ old('walk_in_name', $invoice->walk_in_name) }}">
            </div>
          </div>
        </div>
      </div>

        {{-- Items (editable) --}}
        <div class="card mb-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Ashtya' : 'Items' }}</h6>
            <button type="button" class="btn btn-sm btn-outline-primary addItemBtn">
              <i class="bi bi-plus-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Maal Daalo' : 'Add Item' }}
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered mb-0" id="itemsTable">
                <thead class="table-light">
                  <tr>
                    <th style="min-width:180px">{{ app()->getLocale() === 'ur' ? 'Maal / Size' : 'Product / Size' }}</th>
                    <th style="width:90px">{{ app()->getLocale() === 'ur' ? 'Tadadd' : 'Qty' }}</th>
                    <th style="width:150px">{!! app()->getLocale() === 'ur' ? 'Ikaayi Qeemat' : 'Unit Price' !!}</th>
                    <th style="width:150px">{{ app()->getLocale() === 'ur' ? 'wazan (Kg)' : 'Weight in (Kg)' }}</th>
                    <th style="width:120px">{{ __('Total') }}</th>
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
            <button type="button" class="btn btn-sm btn-outline-primary float-end addItemBtn">
              <i class="bi bi-plus-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Maal Daalo' : 'Add Item' }}
            </button>
          </div>
        </div>

      <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Invoice Meta') }}</h6></div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">{{ __('Invoice Date') }}</label>
              <input type="date" name="invoice_date" class="form-control form-control-sm" value="{{ old('invoice_date', $invoice->invoice_date?->toDateString()) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">{{ __('Due Date') }}</label>
              <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">{{ __('Notes') }}</label>
              <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $invoice->notes) }}">
            </div>
          </div>
        </div>
      </div>

      

    </div>

    <div class="col-lg-4">

      <div class="card mb-3" id="totalsBox">
        <div class="card-header"><h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Khulasa' : 'Summary' }}</h6></div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Upar Kul' : 'Subtotal' }}</span>
            <strong id="subtotalDisplay">PKR {{ number_format($invoice->subtotal, 0) }}</strong>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ __('Discount') }}</span>
            <div class="input-group input-group-sm" style="width:160px;">
              <span class="input-group-text">PKR</span>
              <input type="number" name="discount_amount" id="discountInput"
                     class="form-control text-end" value="{{ old('discount_amount', $invoice->discount_amount) }}"
                     min="0" step="1">
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Wapas Aaya Maal Qeemat' : 'Return Item Amount' }}</span>
            <div class="input-group input-group-sm" style="width:300px;">
              <span class="input-group-text">PKR</span>
              <input type="number" name="return_item_amount" id="returnAmountInput"
                    class="form-control text-end" value="{{ old('return_item_amount', $invoice?->return_item_amount) }}"
                    min="0" step="1">
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Mutbadil Maal Qeemat' : 'Exchange Item Amount' }}</span>
            <div class="input-group input-group-sm" style="width:300px;">
              <span class="input-group-text">PKR</span>
              <input type="number" name="exchange_item_amount" id="exchangeAmountInput"
                    class="form-control text-end" value="{{ old('exchange_item_amount', $invoice?->exchange_item_amount) }}"
                    min="0" step="1">
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ __('NUG') }}</span>
            <div class="input-group input-group-sm" style="width:300px;">
              <input type="number" name="nug" id="nugInput" class="form-control text-end" value="{{ old('nug', $invoice->nug) }}" min="0" step="1" required>
              <span class="input-group-text">×</span>
              <input type="number" name="nug_amount" id="nugAmountInput" class="form-control text-end" value="{{ old('nug_amount', $invoice->nug_amount) }}" min="0" step="1" required>
              <span class="input-group-text">PKR</span>
            </div>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between mb-3">
            <span class="fw-bold">{{ __('Total') }}</span>
            <strong class="fs-5 text-primary" id="computedTotal">PKR {{ number_format($invoice->total_amount, 0) }}</strong>
          </div>

          <input type="hidden" name="subtotal" id="subtotalHidden" value="{{ $invoice->subtotal }}">

          <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Abhi Adaigi Li Gayi' : 'Payment Received Now' }}</label>
          <div class="input-group mb-2">
            <span class="input-group-text"><i class="bi bi-cash"></i></span>
            <input type="number" name="initial_payment" id="initialPayment"
                   class="form-control" value="{{ old('initial_payment', $initialPayment ?? $invoice?->paid_amount ?? 0) }}"
                   min="0" step="1" placeholder="0" @readonly(! $canEditInitialPayment)>
          </div>
          @if (! $canEditInitialPayment)
            <div class="form-text">{{ __('This invoice payment is allocated from a customer receipt. Delete that receipt payment and record the corrected amount instead.') }}</div>
          @endif

          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="1" id="updatePrices" name="update_prices">
            <label class="form-check-label small" for="updatePrices">
              {{ __('Update product prices to match edited item unit prices') }}
            </label>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <button type="submit" class="btn btn-primary w-100">{{ __('Update Invoice') }}</button>
          <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary w-100 mt-2">{{ __('Cancel') }}</a>
        </div>
      </div>

    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {

  var initialItems = {!! json_encode($invoice->items->map(function($it) {
    return [
      'product_size_id' => $it->product_size_id,
      'quantity'        => $it->quantity,
      'weight'          => $it->weight,
      'unit_price'      => $it->unit_price,
      'product_name'    => $it->product_name,
      'product_name_ur' => $it->product_name_ur ?? '',
      'size_label'      => $it->size_label,
      'size_label_ur'   => $it->size_label_ur ?? '',
    ];
  })->values()) !!};

  var rowIndex = 0;

  function buildSizeSelect(index, sizeId = false) {
    let sizeObj = {};
    if (sizeId) {
      sizeObj = initialItems.find(i => i?.product_size_id == sizeId);
      // console.log(sizeObj);
    }
    return `
      <select name="items[${index}][product_size_id]" 
              class="form-select form-select-sm size-select select2-remote" 
              data-url="{{ route('api.search.sizes') }}"
              required>
        <option value="">{{ app()->getLocale() === 'ur' ? '-- Maal/Size Chunein --' : '-- Select Product/Size --' }}</option>
        ${sizeId ? `<option value="${sizeObj?.sizeId}" selected>${sizeObj?.size_label}</option>` : ''}
      </select>
    `;
  }

  function formatSizeOption(data) {
    if (!data.id) return data.text;
    return $('<span>' + data.text + '</span>');
  }

  function formatSizeSelection(data) {
    if (!data.id) return data.text;
    return data.text.substring(0, 50) + (data.text.length > 50 ? '...' : '');
  }

  function addItemRow(sizeId, qty, price, weight = null) {
    qty   = qty    || 1;
    price = price  || '';
    var i  = rowIndex++;
    var row = `
      <tr class="item-row" data-index="${i}">
        <td>
          ${buildSizeSelect(i, sizeId)}
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
            <!--<span class="input-group-text">PKR</span>-->
            <input type="number" name="items[${i}][unit_price]"
                   class="form-control form-control-sm item-price"
                   value="${price}" min="0" step="0.01" ${weight ? 'readonly' : 'required'}>
          </div>
          <span class="text-info">PKR</span>
        </td>
        <td class="weightTd">
          <div class="input-group input-group-sm">
            <!--<span class="input-group-text">(Kg)</span>-->
            <input type="number" name="items[${i}][weight]"
                   class="form-control form-control-sm item-weight "
                   value="${weight}" min="0" step="0.01" ${weight ? 'required' : 'disabled'}>
          </div>
          <span class="text-info">(KG)</span>
        </td>
        <td class="text-end fw-semibold item-total text-primary">PKR 0</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove-item">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>`;
    $('#itemRows').append(row);

    // Initialize Select2 for the new row
    var $select = $(`tr[data-index="${i}"] .size-select`);
    initRemoteSelect($select, '{{ route('api.search.sizes') }}', {
      placeholder: '{{ app()->getLocale() === 'ur' ? 'Naam ya Size se dhoondein...' : 'Search product/size...' }}',
      allowClear: true,
      templateResult: formatSizeOption,
      templateSelection: formatSizeSelection
    });

    // If sizeId provided, pre-select it
    if (sizeId) {
      $.ajax({
        type: 'GET',
        url: '{{ route('api.search.sizes') }}?q=' + sizeId + '&editMode=1',
        success: function(res) {
          if (res.results && res.results.length > 0) {
            var foundSize = res.results.find(s => s.id == sizeId);
            if (foundSize) {
              $select.append(new Option(foundSize.text, foundSize.id, true, true)).trigger('change');
              populateSizeFields($(`tr[data-index="${i}"]`), foundSize);
              if (price) {
                $(`tr[data-index="${i}"] .item-price`).val(price);
              }
            }
          }
        }
      });
    }

    recalcAll();
  }

  function populateSizeFields($row, data) {
    $row.find('.item-product-name').val(data.product_name || '');
    $row.find('.item-product-name-ur').val(data.product_name_ur || '');
    $row.find('.item-size-label').val(data.size_label || '');
    $row.find('.item-size-label-ur').val(data.size_label_ur || '');
    
    if (data.price > 0 && !$row.find('.item-price').val()) {
      $row.find('.item-price').val(data.price);
    }
    
    var stockHtml = data.stock > 0
      ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data.stock + ' {{ app()->getLocale() === 'ur' ? 'Stor mein' : 'in stock' }}</span>'
      : '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>{{ app()->getLocale() === 'ur' ? 'Khatam' : 'Out of stock' }}</span>';
    var purchasePrice = data.purchase_price != 0
      ? '<span class="text-info float-end"><i class="bi bi-cash me-1"></i>' +  ' {{ app()->getLocale() === 'ur' ? 'Qemat-e-Khared' : 'Purchase Price' }} : RS ' + data.purchase_price +'</span>'
      : '';
    $row.find('.item-stock-info').html(`${stockHtml} ${purchasePrice}`);
  }

  function recalcAll() {
    var subtotal = 0;
    $('.item-row').each(function () {
      var qty   = parseFloat($(this).find('.item-qty').val())   || 0;
      var price = parseFloat($(this).find('.item-price').val()) || 0;
      var weight = parseFloat($(this).find('.item-weight').val()) || 0;
      var line  = qty * price;
      if (weight > 0) {
        line  = weight * price;
      }
      $(this).find('.item-total').text('PKR ' + number(line));
      subtotal += line;
    });

    var discount  = parseFloat($('#discountInput').val())   || 0;
    var returnAmountInput  = parseFloat($('#returnAmountInput').val())   || 0;
    var exchangeAmountInput  = parseFloat($('#exchangeAmountInput').val())   || 0;    
    var tax       = parseFloat($('#taxInput').val())        || 0;
    var nugAmount = parseFloat($('#nugAmountInput').val()) || 0;
    var total     = Math.round(Math.max(0, (subtotal - discount - returnAmountInput - exchangeAmountInput) + tax + nugAmount));
    var paid      = parseFloat($('#initialPayment').val())  || 0;
    var remaining = Math.round(Math.max(0, total - paid));

    $('#computedTotal').text('PKR ' + number(total));
    $('#subtotalDisplay').text('PKR ' + number(subtotal));
    $('#subtotalHidden').val(subtotal.toFixed(2));

    var badge = '', cls = '';
    if (total <= 0 || paid >= total) { badge = '{{ __('Paid') }}'; cls = 'bg-success'; }
    else if (paid <= 0) { badge = '{{ __('Pending') }}'; cls = 'bg-danger'; }
    else                    { badge = '{{ __('Semi-Paid') }}';  cls = 'bg-warning text-dark'; }
    $('#statusBadge').attr('class', 'badge ' + cls).text(badge);
  }

  function number(n) {
    return parseFloat(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  // Events
  $(document).on('click', '.remove-item', function () {
    if ($('.item-row').length <= 1) {
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek maal zaroori hai.' : 'At least one item is required.' }}');
      return;
    }
    $(this).closest('tr').remove();
    recalcAll();
  });

  $(document).on('change', '.size-select', function () {
    var $row = $(this).closest('tr');
    var $select = $(this);
    var selectedData = $select.select2('data')[0];
    
    if (selectedData) {
      // $row.find('.item-weight').attr({'disabled': true, 'required' : false}).val();
      if (selectedData.is_weighted == 1) {
        $row.find('.item-price').val(selectedData.price || '');//.attr('readonly', true);
        $row.find('.item-weight').removeAttr('disabled').attr('required', true)
        $row.find('.item-qty').attr('readonly', true)
      } else {
        $row.find('.item-qty').removeAttr('readonly', true)
        $row.find('.item-price').removeAttr('readonly').val(selectedData.price || '');
      }
      populateSizeFields($row, selectedData);
    }
    // recalcAll();
  });

  $(document).on('input', '.item-qty, .item-price, .item-weight, #discountInput, #taxInput, #nugInput, #nugAmountInput, #initialPayment, #returnAmountInput, #exchangeAmountInput', recalcAll);

  $('.addItemBtn').on('click', function () { addItemRow(); });

  // Prefill existing items
  if (initialItems && initialItems.length) {
    $.each(initialItems, function(i, it) {
      addItemRow(it.product_size_id, it.quantity, it.unit_price, it.weight);
    });
    recalcAll();
  } else {
    addItemRow();
  }

  // Ensure subtotal hidden exists for edit form
  if (!$('#subtotalHidden').length) {
    $('<input>').attr({type: 'hidden', id: 'subtotalHidden', name: 'subtotal'}).appendTo('form');
  }

  function clearFormErrors() {
    $('.is-invalid').removeClass('is-invalid');
    $('.field-error-text').remove();
    $('.select2-container--default .select2-selection--single').removeClass('is-invalid');
  }

  function resolveErrorFieldName(field) {
    if (!field || field.includes('[')) {
      return field;
    }

    if (field.includes('.')) {
      var parts = field.split('.');
      return parts.shift() + parts.map(function (part) {
        return '[' + part + ']';
      }).join('');
    }

    return field;
  }

  function showFormErrors(errors) {
    clearFormErrors();

    Object.entries(errors || {}).forEach(function ([field, messages]) {
      var list = Array.isArray(messages) ? messages : [messages];
      var text = list.filter(Boolean).join('<br>');
      var resolvedField = resolveErrorFieldName(field);
      var $field = $('[name="' + resolvedField + '"]');

      if (!$field.length && field === 'customer_id') {
        $field = $('#customerSelect');
      }

      if (!$field.length) {
        $field = $('#' + field);
      }

      if ($field.length) {
        $field.addClass('is-invalid');

        var $selection = $field.next('.select2-container').find('.select2-selection');
        if ($selection.length) {
          $selection.addClass('is-invalid');
        }

        $field.after('<div class="field-error-text">' + text + '</div>');
      }
    });
  }

  // On submit, ensure at least one item
  $('form').on('submit', function (e) {
    e.preventDefault();
    var hasItem = false;
    $('.item-row').each(function () {
      if ($(this).find('.size-select').val()) hasItem = true;
    });
    if (!hasItem) {
      e.preventDefault();
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek maal daalo.' : 'Please add at least one item to the invoice.' }}');
      return false;
    }
    $.ajax({
      url: '{{ route('invoices.update', $invoice) }}',
      method: 'PUT',
      data: $('#invoiceForm').serialize(),
      complete: function() {
        // The form submission has passed validation. Stop autosave from creating
        // another copy. Delete only this draft; other drafts remain untouched.
        isSubmittingInvoice = true;
        clearTimeout(draftSaveTimer);
        deleteCurrentDraft();
        $('#submitBtn').removeAttr('disabled').html('<i class="bi bi-check-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Raseed Banao' : 'Generate Invoice' }}');
      },
      beforeSend: function() {
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ app()->getLocale() === 'ur' ? 'Mehfooz...' : 'Saving...' }}');
      },
      success: function(res) {
        console.log(res);

        var message = res.message || '{{ app()->getLocale() === 'ur' ? 'Raseed safarsh kar diya gaya.' : 'Invoice created successfully.' }}';
        $('#alertMsg')
          .removeClass('alert-danger')
          .addClass('alert alert-success')
          .html(message)
          .show();
        setTimeout(function() {
          location.assign(res?.redirection_url);
        }, 1000);
      },
      error: function(xhr) {
        // console.log(xhr);

        var response = xhr.responseJSON || {};
        var errors = response.errors || {};
        var msg = response.message || '{{ app()->getLocale() === 'ur' ? 'Koi masla hua. Dobara koshish karen.' : 'Something went wrong. Please try again.' }}';

        if (Object.keys(errors).length) {
          msg = Object.values(errors).flat().join('<br>');
          showFormErrors(errors);
        } else {
          clearFormErrors();
        }

        $('#alertMsg')
          .removeClass('alert-success')
          .addClass('alert alert-danger')
          .html(msg)
          .show();
      }
    });
  });

  // initial recalc
  recalcAll();

});
</script>
@endpush
