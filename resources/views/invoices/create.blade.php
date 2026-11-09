@extends('layouts.app')
@section('title', __('New Invoice'))

@push('styles')
<style>
  .item-row td { vertical-align: middle; }
  .item-row .remove-item { opacity: .4; }
  .item-row:hover .remove-item { opacity: 1; }
  #totalsBox { background: var(--card-bg, #fff); }
  .customer-type-btn.active { box-shadow: 0 0 0 2px var(--bs-primary); }
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
    <div class="alert" id="alertMsg"></div>
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
          <label class="form-label small fw-semibold mb-2">{{ app()->getLocale() === 'ur' ? 'Grahak' : 'Customer' }}</label>
          <select id="customerSelect" class="form-control form-control-sm select2-remote"
                  data-url="{{ route('api.search.customers') }}" 
                  data-locale="{{ app()->getLocale() }}"
                  placeholder="{{ app()->getLocale() === 'ur' ? 'Naam ya phone se dhoondein...' : 'Search by name or phone...' }}">
          </select>
          {{-- Previous balance alert --}}
          <div id="prevBalanceAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 d-flex justify-content-between align-items-center" style="display:none!important;">
            <div>
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <span class="fw-semibold small" id="prevBalanceLabel">{{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi:' : 'Previous Outstanding:' }}</span>
            </div>
            <strong id="prevBalanceAmount" class="fs-6">PKR 0</strong>
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
        <div>
          <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="restoreDraftBtn">
            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ app()->getLocale() === 'ur' ? 'Draft Lagao' : 'Restore Draft' }}
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger me-2" id="clearDraftBtn">
            <i class="bi bi-trash me-1"></i> {{ app()->getLocale() === 'ur' ? 'Draft Saaf' : 'Clear Draft' }}
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary addItemBtn">
            <i class="bi bi-plus-lg me-1"></i> {{ app()->getLocale() === 'ur' ? 'Maal Daalo' : 'Add Item' }}
          </button>
        </div>
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
          <div class="input-group input-group-sm" style="width:300px;">
            <span class="input-group-text">PKR</span>
            <input type="number" name="discount_amount" id="discountInput"
                   class="form-control text-end" value="{{ old('discount_amount', 0) }}"
                   min="0" step="1">
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Wapas Aaya Maal Qeemat' : 'Return Item Amount' }}</span>
          <div class="input-group input-group-sm" style="width:300px;">
            <span class="input-group-text">PKR</span>
            <input type="number" name="return_item_amount" id="returnAmountInput"
                   class="form-control text-end" value="{{ old('return_item_amount', 0) }}"
                   min="0" step="1">
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted">{{ app()->getLocale() === 'ur' ? 'Mutbadil Maal Qeemat' : 'Exchange Item Amount' }}</span>
          <div class="input-group input-group-sm" style="width:300px;">
            <span class="input-group-text">PKR</span>
            <input type="number" name="exchange_item_amount" id="exchangeAmountInput"
                   class="form-control text-end" value="{{ old('exchange_item_amount', 0) }}"
                   min="0" step="1">
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted">{{ __('NUG') }}</span>
          <div class="input-group input-group-sm" style="width:300px;">
            <input type="number" name="nug" id="nugInput" class="form-control text-end" value="{{ old('nug', 0) }}" min="0" step="1" required>
            <span class="input-group-text">×</span>
            <input type="number" name="nug_amount" id="nugAmountInput" class="form-control text-end" value="{{ old('nug_amount', 0) }}" min="0" required>
            <span class="input-group-text">PKR</span>
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
          <option value="cheque">Cheque</option>
          <option value="bank">{{ app()->getLocale() === 'ur' ? 'Bank Tabadla' : 'Bank Transfer' }}</option>
          <option value="other">{{ app()->getLocale() === 'ur' ? 'Aur' : 'Other' }}</option>
        </select>

        {{-- Previous Balance row (hidden until regular customer with balance selected) --}}
        <div id="summaryPrevBalanceRow" class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="display:none!important;background:#fff3cd;border:1px solid #ffc107;">
            <span class="small fw-semibold" id="summaryPrevBalanceLabel">
            <i class="bi bi-clock-history me-1"></i>
              <span id="summaryPrevBalanceLabelText">{{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi' : 'Prev. Balance' }}</span>
          </span>
          <strong id="summaryPrevBalance" class="text-danger">PKR 0</strong>
        </div>

        <div class="alert py-2 mb-0" id="statusPreview">
          <div class="d-flex justify-content-between">
            <span class="small">{{ app()->getLocale() === 'ur' ? 'Is Bill ka Baqi' : 'This Bill Due' }}</span>
            <strong id="remainingDisplay">PKR 0</strong>
          </div>
          <div class="d-flex justify-content-between mt-1 text-warning-emphasis" id="cashRefundPreviewRow" style="display:none!important;">
            <span class="small">{{ app()->getLocale() === 'ur' ? 'واپس کی جانے والی نقد رقم' : 'Cash Refund (Paid Now)' }}</span>
            <strong id="cashRefundPreview">PKR 0</strong>
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

{{-- Saved invoice drafts --}}
<div class="modal fade" id="draftModal" tabindex="-1" aria-labelledby="draftModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="draftModalLabel">
          <i class="bi bi-journal-text me-1"></i>
          {{ app()->getLocale() === 'ur' ? 'Saved Drafts' : 'Saved Invoice Drafts' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="draftList"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

  var rowIndex = 0;

  /* ==========================================================
   * Invoice Draft Manager
   * ----------------------------------------------------------
   * - Stores multiple invoice drafts in one localStorage key.
   * - Saves automatically (debounced) after meaningful changes.
   * - Stores Select2 item data so restoration does not depend on
   *   an AJAX request finishing before recalculate/save runs.
   * - Keeps at most 30 drafts and removes drafts older than 30 days.
   * - Removes the current draft after a successful invoice submit.
   * ========================================================== */
  const DRAFTS_KEY = 'invoice_drafts_v2';
  const MAX_DRAFTS = 30;
  const MAX_DRAFT_AGE = 30 * 24 * 60 * 60 * 1000;
  const DRAFT_SAVE_DELAY = 500;

  let currentDraftId = null;
  let draftSaveTimer = null;
  let isRestoringDraft = false;
  let isSubmittingInvoice = false;

  function generateDraftId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return 'd_' + window.crypto.randomUUID();
    }
    return 'd_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
  }

  function readDrafts() {
    try {
      const raw = localStorage.getItem(DRAFTS_KEY);
      const drafts = raw ? JSON.parse(raw) : [];
      return Array.isArray(drafts) ? drafts : [];
    } catch (e) {
      console.warn('Could not read invoice drafts:', e);
      return [];
    }
  }

  function writeDrafts(drafts) {
    try {
      localStorage.setItem(DRAFTS_KEY, JSON.stringify(drafts));
      return true;
    } catch (e) {
      console.warn('Could not save invoice drafts. Trying cleanup:', e);

      // QuotaExceededError: remove oldest drafts until there is room.
      try {
        drafts.sort((a, b) => (b.saved_at || 0) - (a.saved_at || 0));
        while (drafts.length > 1) {
          drafts.pop();
          try {
            localStorage.setItem(DRAFTS_KEY, JSON.stringify(drafts));
            return true;
          } catch (retryError) {
            // Keep removing the oldest draft.
          }
        }
      } catch (cleanupError) {
        console.error('Draft storage cleanup failed:', cleanupError);
      }
    }
    return false;
  }

  function cleanupDrafts(drafts) {
    const cutoff = Date.now() - MAX_DRAFT_AGE;

    drafts = drafts.filter(d => d && d.id && (d.saved_at || 0) >= cutoff);
    drafts.sort((a, b) => (b.saved_at || 0) - (a.saved_at || 0));

    if (drafts.length > MAX_DRAFTS) {
      drafts = drafts.slice(0, MAX_DRAFTS);
    }

    return drafts;
  }

  function getDraftSummary(draft) {
    const items = Array.isArray(draft.items) ? draft.items : [];
    const customer = draft.customer_id
      ? (draft.customer_name || ('Customer #' + draft.customer_id))
      : (draft.walk_in_name || 'Walk-in Customer');

    return {
      customer: customer,
      itemCount: items.length,
      savedAt: draft.saved_at || Date.now()
    };
  }

  function formatDraftDate(timestamp) {
    try {
      return new Date(timestamp).toLocaleString('en-PK', {
        dateStyle: 'medium',
        timeStyle: 'short'
      });
    } catch (e) {
      return new Date(timestamp).toLocaleString();
    }
  }

  function getCurrentItemsForDraft() {
    const items = [];

    $('.item-row').each(function () {
      const $row = $(this);
      const $sel = $row.find('.size-select');
      let sizeId = $sel.val() || '';
      let selectedData = null;

      try {
        selectedData = $sel.select2('data')[0] || null;
      } catch (e) {}

      if (!sizeId && selectedData && selectedData.id) {
        sizeId = selectedData.id;
      }

      // Do not store completely empty rows. This is important because the
      // first blank row is intentionally created when the page opens.
      if (!sizeId) return;

      items.push({
        sizeId: String(sizeId),
        qty: parseFloat($row.find('.item-qty').val()) || 1,
        price: parseFloat($row.find('.item-price').val()) || 0,
        weight: parseFloat($row.find('.item-weight').val()) || 0,
        product_name: $row.find('.item-product-name').val() || '',
        product_name_ur: $row.find('.item-product-name-ur').val() || '',
        size_label: $row.find('.item-size-label').val() || '',
        size_label_ur: $row.find('.item-size-label-ur').val() || '',
        // Keep the Select2 object so restore can rebuild the selected option
        // immediately without waiting for /api/search/sizes.
        select_data: selectedData ? {
          id: selectedData.id,
          text: selectedData.text || '',
          product_name: selectedData.product_name || '',
          product_name_ur: selectedData.product_name_ur || '',
          size_label: selectedData.size_label || '',
          size_label_ur: selectedData.size_label_ur || '',
          is_weighted: selectedData.is_weighted || 0,
          price: selectedData.price || 0,
          stock: selectedData.stock || 0,
          purchase_price: selectedData.purchase_price || 0
        } : null
      });
    });

    return items;
  }

  function collectDraft() {
    const items = getCurrentItemsForDraft();

    return {
      id: currentDraftId || generateDraftId(),
      customer_id: $('#customerId').val() || '',
      customer_name: $('#customerSelect').find('option:selected').text() || '',
      walk_in_name: $('input[name="walk_in_name"]').val() || '',
      walk_in_phone: $('input[name="walk_in_phone"]').val() || '',
      invoice_date: $('input[name="invoice_date"]').val() || '',
      due_date: $('input[name="due_date"]').val() || '',
      notes: $('input[name="notes"]').val() || '',
      discount_amount: $('#discountInput').val() || 0,
      return_item_amount: $('#returnAmountInput').val() || 0,
      exchange_item_amount: $('#exchangeAmountInput').val() || 0,
      nug: $('#nugInput').val() || 0,
      nug_amount: $('#nugAmountInput').val() || 0,
      initial_payment: $('#initialPayment').val() || 0,
      payment_method: $('select[name="payment_method"]').val() || 'cash',
      items: items,
      saved_at: Date.now()
    };
  }

  function draftHasMeaningfulData(draft) {
    if (!draft) return false;
    if (Array.isArray(draft.items) && draft.items.length > 0) return true;
    if (draft.customer_id || draft.walk_in_name || draft.walk_in_phone || draft.due_date || draft.notes) return true;
    if (Number(draft.discount_amount) || Number(draft.return_item_amount) || Number(draft.exchange_item_amount)) return true;
    if (Number(draft.nug) || Number(draft.nug_amount) || Number(draft.initial_payment)) return true;
    return false;
  }

  function saveDraft() {
    if (isRestoringDraft || isSubmittingInvoice) return;

    try {
      let draft = collectDraft();

      // Do not create storage entries for an untouched blank invoice.
      if (!draftHasMeaningfulData(draft)) return;

      currentDraftId = draft.id;
      let drafts = cleanupDrafts(readDrafts());
      const existingIndex = drafts.findIndex(d => d.id === currentDraftId);

      if (existingIndex >= 0) {
        drafts[existingIndex] = draft;
      } else {
        drafts.unshift(draft);
      }

      drafts = cleanupDrafts(drafts);

      if (writeDrafts(drafts)) {
        updateDraftButtons();
      }
    } catch (e) {
      console.error('Failed to save invoice draft:', e);
    }
  }

  function scheduleDraftSave() {
    if (isRestoringDraft || isSubmittingInvoice) return;
    clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(saveDraft, DRAFT_SAVE_DELAY);
  }

  function deleteDraft(draftId) {
    let drafts = cleanupDrafts(readDrafts()).filter(d => d.id !== draftId);
    writeDrafts(drafts);

    if (currentDraftId === draftId) {
      currentDraftId = null;
    }

    updateDraftButtons();
  }

  function deleteCurrentDraft() {
    if (!currentDraftId) return;
    deleteDraft(currentDraftId);
  }

  function updateDraftButtons() {
    const drafts = cleanupDrafts(readDrafts());
    const count = drafts.length;
    const $restore = $('#restoreDraftBtn');
    const $clear = $('#clearDraftBtn');

    if (count > 0) {
      $restore.removeClass('disabled').prop('disabled', false)
        .html('<i class="bi bi-arrow-counterclockwise me-1"></i> ' +
          ({{ app()->getLocale() === 'ur' ? 'true' : 'false' }} ? 'Draft Lagao (' + count + ')' : 'Restore Draft (' + count + ')'));
    } else {
      $restore.addClass('disabled').prop('disabled', true)
        .html('<i class="bi bi-arrow-counterclockwise me-1"></i> ' +
          ({{ app()->getLocale() === 'ur' ? 'true' : 'false' }} ? 'Draft Lagao' : 'Restore Draft'));
    }

    $clear.prop('disabled', count === 0);

    if (drafts.length) {
      writeDrafts(drafts);
    }
  }

  function showDraftList() {
    const drafts = cleanupDrafts(readDrafts());
    writeDrafts(drafts);

    const $list = $('#draftList');
    $list.empty();

    if (!drafts.length) {
      $list.html('<div class="text-center text-muted py-4">' +
        '{{ app()->getLocale() === 'ur' ? 'Koi saved draft nahi mila.' : 'No saved drafts found.' }}' +
        '</div>');
      $('#draftModal').modal('show');
      return;
    }

    drafts.forEach(function (draft) {
      const summary = getDraftSummary(draft);
      const active = draft.id === currentDraftId;
      const customer = $('<div>').text(summary.customer).html();
      const date = $('<div>').text(formatDraftDate(summary.savedAt)).html();
      const itemLabel = summary.itemCount === 1 ? 'item' : 'items';

      $list.append(`
        <div class="border rounded p-3 mb-2 ${active ? 'border-primary bg-light' : ''}">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-semibold">${customer}</div>
              <div class="small text-muted">${summary.itemCount} ${itemLabel} &bull; ${date}</div>
            </div>
            <div class="text-nowrap">
              <button type="button" class="btn btn-sm btn-primary restore-one-draft" data-draft-id="${draft.id}">
                <i class="bi bi-arrow-counterclockwise me-1"></i>{{ app()->getLocale() === 'ur' ? 'Bahaa' : 'Restore' }}
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger delete-one-draft" data-draft-id="${draft.id}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      `);
    });

    $('#draftModal').modal('show');
  }

  function restoreDraftById(draftId) {
    const drafts = cleanupDrafts(readDrafts());
    const draft = drafts.find(d => d.id === draftId);

    if (!draft) {
      alert('{{ app()->getLocale() === 'ur' ? 'Draft nahi mila.' : 'Draft could not be found.' }}');
      updateDraftButtons();
      return null;
    }

    isRestoringDraft = true;
    currentDraftId = draft.id;

    try {
      $('#itemRows').empty();
      rowIndex = 0;

      // Restore customer/customer type first. For regular customers we can
      // restore the selected option immediately and let the existing balance
      // handler fetch the current balance.
      if (draft.customer_id) {
        $('[data-type="regular"]').trigger('click');
        const customerText = draft.customer_name || ('Customer #' + draft.customer_id);
        $('#customerSelect').append(new Option(customerText, draft.customer_id, true, true)).trigger('change');
        $('#customerId').val(draft.customer_id);
      } else {
        $('[data-type="walkin"]').trigger('click');
        $('input[name="walk_in_name"]').val(draft.walk_in_name || '');
        $('input[name="walk_in_phone"]').val(draft.walk_in_phone || '');
      }

      $('input[name="invoice_date"]').val(draft.invoice_date || '');
      $('input[name="due_date"]').val(draft.due_date || '');
      $('input[name="notes"]').val(draft.notes || '');
      $('#discountInput').val(draft.discount_amount || 0);
      $('#returnAmountInput').val(draft.return_item_amount || 0);
      $('#exchangeAmountInput').val(draft.exchange_item_amount || 0);
      $('#nugInput').val(draft.nug || 0);
      $('#nugAmountInput').val(draft.nug_amount || 0);
      $('#initialPayment').val(draft.initial_payment || 0);
      $('select[name="payment_method"]').val(draft.payment_method || 'cash');

      if (Array.isArray(draft.items) && draft.items.length) {
        draft.items.forEach(function (item) {
          addItemRow(item, true);
        });
      } else {
        addItemRow();
      }

      recalculate();
    } catch (e) {
      console.error('Failed to restore draft:', e);
      isRestoringDraft = false;
      return null;
    }

    // addItemRow initializes Select2 synchronously, but restoration of each
    // Select2 option is also synchronous when select_data exists. Release the
    // guard on the next tick so no intermediate AJAX/change event can wipe the
    // saved draft.
    setTimeout(function () {
      isRestoringDraft = false;
      scheduleDraftSave();
    }, 100);

    $('#draftModal').modal('hide');
    console.log('Restored draft:', draft.id, draft.items || []);

    return draft;
  }

  function clearAllDrafts() {
    localStorage.removeItem(DRAFTS_KEY);
    currentDraftId = null;
    updateDraftButtons();
  }

  function buildSizeSelect(index) {
    return `
      <select name="items[${index}][product_size_id]"
              class="form-select form-select-sm size-select select2-remote"
              data-url="{{ route('api.search.sizes') }}"
              required>
        <option value="">{{ app()->getLocale() === 'ur' ? '-- Maal/Size Chunein --' : '-- Select Product/Size --' }}</option>
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

  function addItemRow(itemOrSizeId, isRestoring) {
    // Backward-compatible arguments: addItemRow(), addItemRow(sizeId), and
    // the new addItemRow(draftItem, true).
    let item = {};
    if (itemOrSizeId && typeof itemOrSizeId === 'object') {
      item = itemOrSizeId;
    } else if (itemOrSizeId) {
      item = { sizeId: itemOrSizeId };
    }

    const sizeId = item.sizeId || '';
    const qty = item.qty || 1;
    const price = item.price || '';
    const weight = item.weight || '';
    const restoreData = item.select_data || null;

    var i = rowIndex++;
    var row = `
      <tr class="item-row" data-index="${i}">
        <td>
          ${buildSizeSelect(i)}
          <input type="hidden" name="items[${i}][product_name]" class="item-product-name">
          <input type="hidden" name="items[${i}][product_name_ur]" class="item-product-name-ur">
          <input type="hidden" name="items[${i}][size_label]" class="item-size-label">
          <input type="hidden" name="items[${i}][size_label_ur]" class="item-size-label-ur">
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
                   value="${price}" min="0" required>
          </div>
          <span class="text-info">PKR</span>
        </td>
        <td class="weightTd">
          <div class="input-group input-group-sm">
            <!--<span class="input-group-text">(Kg)</span>-->
            <input type="number" name="items[${i}][weight]"
                   class="form-control form-control-sm item-weight"
                   value="${weight}" min="0" step="0.01" disabled>
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

    var $row = $(`tr[data-index="${i}"]`);
    var $select = $row.find('.size-select');

    initRemoteSelect($select, '{{ route('api.search.sizes') }}', {
      placeholder: '{{ app()->getLocale() === 'ur' ? 'Naam ya Size se dhoondein...' : 'Search product/size...' }}',
      allowClear: true,
      templateResult: formatSizeOption,
      templateSelection: formatSizeSelection
    });

    if (restoreData && restoreData.id) {
      // Best path: restore the complete Select2 data locally, without AJAX.
      const option = new Option(restoreData.text || ('Size #' + restoreData.id), restoreData.id, true, true);
      $select.append(option).trigger('change');
      $select.trigger({
        type: 'select2:select',
        params: { data: restoreData }
      });

      populateSizeFields($row, restoreData);
      $row.find('.item-price').val(price || restoreData.price || '');
      $row.find('.item-weight').val(weight || '');

      if (restoreData.is_weighted == 1) {
        $row.find('.item-weight').removeAttr('disabled').attr('required', true);
        $row.find('.item-qty').attr('readonly', true);
      } else {
        $row.find('.item-weight').attr('disabled', true).attr('required', false);
        $row.find('.item-qty').removeAttr('readonly');
      }
    } else if (sizeId) {
      // Legacy/old draft fallback. Only use AJAX when old saved data does not
      // contain select_data.
      $.ajax({
        type: 'GET',
        url: '{{ route('api.search.sizes') }}?q=' + encodeURIComponent(sizeId),
        success: function(res) {
          if (res.results && res.results.length > 0) {
            var foundSize = res.results.find(s => s.id == sizeId);
            if (foundSize) {
              $select.append(new Option(foundSize.text, foundSize.id, true, true)).trigger('change');
              populateSizeFields($row, foundSize);
              if (price) {
                $row.find('.item-price').val(price);
              }
            }
          }
          recalculate();
        },
        error: function() {
          console.warn('Could not reload size while restoring draft:', sizeId);
        }
      });
    }

    recalculate();
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
      ? '<span class="text-info float-end"><i class="bi bi-cash me-1"></i>' + ' {{ app()->getLocale() === 'ur' ? 'Qemat-e-Khared' : 'Purchase Price' }} : RS ' + data.purchase_price + '</span>'
      : '';

    $row.find('.item-stock-info').html(`${stockHtml} ${purchasePrice}`);
  }

  addItemRow();
  updateDraftButtons();

  $('.addItemBtn').on('click', function () {
    addItemRow();
    scheduleDraftSave();
  });

  $(document).on('click', '.remove-item', function () {
    if ($('.item-row').length <= 1) {
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek maal zaroori hai.' : 'At least one item is required.' }}');
      return;
    }
    $(this).closest('tr').remove();
    recalculate();
    scheduleDraftSave();
  });

  $(document).on('change', '.size-select', function (e) {
    var $row = $(this).closest('tr');
    var $select = $(this);
    var selectedData = null;

    try {
      selectedData = $select.select2('data')[0];
    } catch (error) {}

    // When restoring, the explicit select2:select handler below carries the
    // richer saved object. Normal user selections use this handler.
    if (selectedData) {
      $row.find('.item-weight').attr({'disabled': true, 'required': false}).val('');
      if (selectedData.is_weighted == 1) {
        $row.find('.item-price').val(selectedData.price || '');
        $row.find('.item-weight').removeAttr('disabled').attr('required', true);
        $row.find('.item-qty').attr('readonly', true);
      } else {
        $row.find('.item-qty').removeAttr('readonly');
        $row.find('.item-price').removeAttr('readonly').val(selectedData.price || '');
      }
      populateSizeFields($row, selectedData);
    }
    recalculate();
    scheduleDraftSave();
  });

  $(document).on('select2:select', '.size-select', function (e) {
    const $select = $(this);
    const $row = $select.closest('tr');
    const selectedData = (e.params && e.params.data) ? e.params.data : ($select.select2('data')[0] || null);

    if (selectedData) {
      populateSizeFields($row, selectedData);
      if (selectedData.is_weighted == 1) {
        $row.find('.item-weight').removeAttr('disabled').attr('required', true);
        $row.find('.item-qty').attr('readonly', true);
      } else {
        $row.find('.item-weight').attr('disabled', true).attr('required', false).val('');
        $row.find('.item-qty').removeAttr('readonly');
      }
    }

    setTimeout(function () {
      recalculate();
      scheduleDraftSave();
    }, 60);
  });

  $(document).on('select2:unselect', '.size-select', function () {
    setTimeout(function () {
      recalculate();
      scheduleDraftSave();
    }, 60);
  });

  // Save the latest meaningful state if the browser is closing/refreshed.
  window.addEventListener('beforeunload', function () {
    try {
      clearTimeout(draftSaveTimer);
      saveDraft();
    } catch (e) {}
  });

  $(document).on('input change',
    '.item-qty, .item-price, .item-weight, #discountInput, #nugInput, #nugAmountInput, #initialPayment, #returnAmountInput, #exchangeAmountInput, input[name="walk_in_name"], input[name="walk_in_phone"], input[name="invoice_date"], input[name="due_date"], input[name="notes"], select[name="payment_method"]',
    function () {
      recalculate();
      scheduleDraftSave();
    }
  );

  function recalculate() {
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
    var tax       = parseFloat($('input[name="tax_amount"]').val()) || 0;
    var nugAmount = parseFloat($('#nugAmountInput').val()) || 0;
    var total     = Math.round(Math.max(0, (subtotal - discount - returnAmountInput - exchangeAmountInput) + tax + nugAmount));
    var paid      = parseFloat($('#initialPayment').val())  || 0;
    var prevBal = window._prevBalance || 0;
    var advanceApplied = $('#customerId').val() && prevBal < 0
      ? Math.min(Math.max(0, total - paid), Math.abs(prevBal))
      : 0;
    var remaining = Math.round(Math.max(0, total - paid - advanceApplied));

    $('#subtotalDisplay').text('PKR ' + number(subtotal));
    $('#totalDisplay').text('PKR ' + number(total));
    $('#subtotalHidden').val(subtotal.toFixed(2));
    $('#remainingDisplay').text('PKR ' + number(remaining));

    var badge = '', cls = '';
    if (total <= 0 || paid + advanceApplied >= total) { badge = '{{ __('Paid') }}'; cls = 'bg-success'; }
    else if (paid <= 0) { badge = '{{ __('Pending') }}'; cls = 'bg-danger'; }
    else                    { badge = '{{ __('Semi-Paid') }}';  cls = 'bg-warning text-dark'; }
    $('#statusBadge').attr('class', 'badge ' + cls).text(badge);

    var gross = Math.max(0, subtotal - discount + tax + nugAmount);
    var adjustmentCredit = Math.max(0, returnAmountInput + exchangeAmountInput - gross);
    var isWalkIn = !$('#customerId').val();
    var cashRefund = isWalkIn ? Math.max(0, paid + returnAmountInput + exchangeAmountInput - gross) : 0;
    var paymentApplied = isWalkIn ? Math.min(paid, total) : paid;
    $('#cashRefundPreview').text('PKR ' + number(cashRefund));
    $('#cashRefundPreviewRow').toggle(cashRefund > 0);

    var accountAfterInvoice = prevBal + total - paymentApplied - adjustmentCredit;
    if (accountAfterInvoice !== 0) {
      $('#totalOutstandingRow span').text(accountAfterInvoice > 0 ? '{{ app()->getLocale() === 'ur' ? 'Kul Baqi' : 'Total Outstanding' }}' : '{{ app()->getLocale() === 'ur' ? 'Dastiyab Advance' : 'Available Advance' }}');
      $('#totalOutstandingDisplay')
        .text('PKR ' + number(Math.abs(accountAfterInvoice)))
        .toggleClass('text-danger', accountAfterInvoice > 0)
        .toggleClass('text-success', accountAfterInvoice < 0);
      $('#totalOutstandingRow').show();
    } else {
      $('#totalOutstandingRow').hide();
    }
  }

  // Draft controls
  $('#restoreDraftBtn').on('click', function () {
    showDraftList();
  });

  $(document).on('click', '.restore-one-draft', function () {
    restoreDraftById($(this).data('draft-id'));
  });

  $(document).on('click', '.delete-one-draft', function () {
    const id = $(this).data('draft-id');
    if (confirm('{{ app()->getLocale() === 'ur' ? 'Kya aap is draft ko delete karna chahte hain?' : 'Delete this draft?' }}')) {
      deleteDraft(id);
      showDraftList();
    }
  });

  $('#clearDraftBtn').on('click', function () {
    const drafts = cleanupDrafts(readDrafts());
    if (!drafts.length) return;

    if (confirm('{{ app()->getLocale() === 'ur' ? 'Kya aap tamam saved drafts saaf karna chahte hain?' : 'Clear ALL saved invoice drafts?' }}')) {
      clearAllDrafts();
      alert('{{ app()->getLocale() === 'ur' ? 'Tamam drafts saaf kar diye gaye.' : 'All saved drafts cleared.' }}');
    }
  });

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
      $('#customerSelect').val(null).trigger('change');
    } else {
      $('#walkinFields').hide();
      $('#regularFields').show();
      // Initialize Select2 if not already initialized
      if (!$('#customerSelect').data('select2')) {
        initRemoteSelect('#customerSelect', '{{ route('api.search.customers') }}', {
          placeholder: "{{ app()->getLocale() === 'ur' ? 'Naam ya phone se dhoondein...' : 'Search by name or phone...' }}",
          allowClear: true
        });
      }
      $('#customerSelect').focus();
    }
  });

  // Initialize Select2 for customer select
  initRemoteSelect('#customerSelect', '{{ route('api.search.customers') }}', {
    placeholder: "{{ app()->getLocale() === 'ur' ? 'Naam ya phone se dhoondein...' : 'Search by name or phone...' }}",
    allowClear: true
  });

  @if(request('customer_id'))
    $('[data-type="regular"]').trigger('click');
    // Pre-load the selected customer
    $.ajax({
      type: 'GET',
      url: '{{ route('customers.show', [':id', "createInvoice" => 1]) }}'.replace(':id', {{ (int) request('customer_id') }}),
      success: function(data) {
        $('#customerSelect').append(new Option(data.name + (data.phone ? ' (' + data.phone + ')' : ''), data.id, true, true)).trigger('change');
      }
    });
  @endif

  // Handle customer selection change for previous balance
  $('#customerSelect').on('change', function() {
    var customerId = $(this).val();
    if (customerId) {
      $.getJSON('{{ url('customers') }}/' + customerId + '/balance', function(res) {
        if (res.has_balance || res.has_advance) {
          var hasAdvance = res.has_advance;
          $('#prevBalanceAmount').text(res.formatted);
          $('#prevBalanceLabel').text(hasAdvance ? '{{ app()->getLocale() === 'ur' ? 'Dastiyab Advance:' : 'Available Advance:' }}' : '{{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi:' : 'Previous Outstanding:' }}');
          $('#prevBalanceAlert')
            .toggleClass('alert-danger', !hasAdvance)
            .toggleClass('alert-success', hasAdvance)
            .show();
          $('#summaryPrevBalance').text(res.formatted);
          $('#summaryPrevBalanceLabelText').text(hasAdvance ? '{{ app()->getLocale() === 'ur' ? 'Dastiyab Advance' : 'Available Advance' }}' : '{{ app()->getLocale() === 'ur' ? 'Pehle ka Baqi' : 'Prev. Balance' }}');
          $('#summaryPrevBalanceRow').show();
          window._prevBalance = res.balance;
        } else {
          $('#prevBalanceAlert').hide();
          $('#summaryPrevBalanceRow').hide();
          window._prevBalance = 0;
        }
        recalculate();
      });
    } else {
      $('#prevBalanceAlert').hide();
      $('#summaryPrevBalanceRow').hide();
      window._prevBalance = 0;
      recalculate();
    }
  });

  // Sync hidden field with Select2 value
  $('#customerSelect').on('select2:select', function() {
    $('#customerId').val($(this).val());
  }).on('select2:clear', function() {
    $('#customerId').val('');
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#regularFields').length) {
      $('#customerDropdown').hide();
    }
  });

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

  $('#invoiceForm').on('submit', function (e) {
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
      url: '{{ route('invoices.store') }}',
      method: 'POST',
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

});
</script>
@endpush
