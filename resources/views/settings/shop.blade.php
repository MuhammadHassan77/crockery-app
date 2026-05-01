@extends('layouts.app')
@section('title', __('Shop Settings'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Shop Settings') }}</h4>
    <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Bill aur app ki malumat update karein' : 'Update your shop information & bill details' }}</small>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<form action="{{ route('settings.update') }}" method="POST">
  @csrf @method('PUT')

  <div class="row g-3">

    {{-- ── Left column ── --}}
    <div class="col-lg-8">

      {{-- Shop Identity --}}
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-shop fs-5 text-primary"></i>
          <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Dukan ki Pehchan' : 'Shop Identity' }}</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="form-label fw-semibold">
                {{ app()->getLocale() === 'ur' ? 'Dukan ka Naam' : 'Shop Name' }}
                <span class="text-danger">*</span>
              </label>
              <input type="text" name="shop_name" class="form-control @error('shop_name') is-invalid @enderror"
                     value="{{ old('shop_name', $settings['shop_name']) }}"
                     placeholder="e.g. Bartan Shop" required>
              @error('shop_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Bill ke upar bada naam.' : 'Appears as the large heading on printed bills.' }}</div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">
                {{ app()->getLocale() === 'ur' ? 'Tagline' : 'Tagline / Sub-heading' }}
              </label>
              <input type="text" name="shop_tagline" class="form-control @error('shop_tagline') is-invalid @enderror"
                     value="{{ old('shop_tagline', $settings['shop_tagline']) }}"
                     placeholder="e.g. Crockery & Household Items">
              @error('shop_tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

      {{-- Contact & Location --}}
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-geo-alt fs-5 text-success"></i>
          <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Rabta aur Pata' : 'Contact & Location' }}</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Pata' : 'Address' }}</label>
              <input type="text" name="shop_address" class="form-control @error('shop_address') is-invalid @enderror"
                     value="{{ old('shop_address', $settings['shop_address']) }}"
                     placeholder="{{ app()->getLocale() === 'ur' ? 'maslan: Main Bazaar, Gujranwala' : 'e.g. Main Bazaar, Gujranwala' }}">
              @error('shop_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Shehar' : 'City' }}</label>
              <input type="text" name="shop_city" class="form-control @error('shop_city') is-invalid @enderror"
                     value="{{ old('shop_city', $settings['shop_city']) }}"
                     placeholder="e.g. Gujranwala">
              @error('shop_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">
                <i class="bi bi-telephone me-1"></i>
                {{ app()->getLocale() === 'ur' ? 'Phone 1' : 'Phone 1' }}
              </label>
              <input type="text" name="shop_phone" class="form-control @error('shop_phone') is-invalid @enderror"
                     value="{{ old('shop_phone', $settings['shop_phone']) }}"
                     placeholder="0300-0000000">
              @error('shop_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">
                <i class="bi bi-telephone me-1"></i>
                {{ app()->getLocale() === 'ur' ? 'Phone 2 (Ikhtiyari)' : 'Phone 2 (optional)' }}
              </label>
              <input type="text" name="shop_phone2" class="form-control @error('shop_phone2') is-invalid @enderror"
                     value="{{ old('shop_phone2', $settings['shop_phone2']) }}"
                     placeholder="0305-0000000">
              @error('shop_phone2')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">
                <i class="bi bi-envelope me-1"></i>
                {{ app()->getLocale() === 'ur' ? 'Email (Ikhtiyari)' : 'Email (optional)' }}
              </label>
              <input type="email" name="shop_email" class="form-control @error('shop_email') is-invalid @enderror"
                     value="{{ old('shop_email', $settings['shop_email']) }}"
                     placeholder="shop@example.com">
              @error('shop_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

      {{-- Bill Settings --}}
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-receipt fs-5 text-warning"></i>
          <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Bill ki Settings' : 'Bill / Print Settings' }}</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label fw-semibold">
                {{ app()->getLocale() === 'ur' ? 'Bill Footer Note' : 'Bill Footer Note' }}
              </label>
              <input type="text" name="bill_footer_note" class="form-control @error('bill_footer_note') is-invalid @enderror"
                     value="{{ old('bill_footer_note', $settings['bill_footer_note']) }}"
                     placeholder="شکریہ — خریداری کرنے کا شکریہ!">
              @error('bill_footer_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Har bill ke neeche dikhega.' : 'Shown at the bottom of every printed bill.' }}</div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">
                {{ app()->getLocale() === 'ur' ? 'Default Kam Stor Hadd' : 'Default Low Stock Threshold' }}
              </label>
              <div class="input-group">
                <input type="number" name="low_stock_default" class="form-control @error('low_stock_default') is-invalid @enderror"
                       value="{{ old('low_stock_default', $settings['low_stock_default'] ?? 5) }}"
                       min="0" max="9999">
                <span class="input-group-text">{{ app()->getLocale() === 'ur' ? 'units' : 'units' }}</span>
              </div>
              @error('low_stock_default')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">{{ app()->getLocale() === 'ur' ? 'Naye products mein default threshold.' : 'Default threshold for new product sizes.' }}</div>
            </div>

          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-check-lg me-1"></i> {{ __('Save Changes') }}
        </button>
      </div>

    </div>

    {{-- ── Right column: Bill Preview ── --}}
    <div class="col-lg-4">
      <div class="card sticky-top" style="top:80px;">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-eye fs-5"></i>
          <h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Bill Preview' : 'Bill Header Preview' }}</h6>
        </div>
        <div class="card-body p-0">
          {{-- Live preview box --}}
          <div id="billPreview" style="padding:16px;background:#fff;border-bottom:3px double #333;text-align:center;font-family:Arial,sans-serif;">
            <div id="previewName" style="font-size:20px;font-weight:900;text-decoration:underline;margin-bottom:3px;">
              {{ $settings['shop_name'] ?: 'Bartan Shop' }}
            </div>
            <div id="previewTagline" style="font-size:12px;color:#555;margin-bottom:2px;">
              {{ $settings['shop_tagline'] ?: 'Crockery & Household Items' }}
            </div>
            <div id="previewAddress" style="font-size:12px;color:#555;margin-bottom:2px;">
              {{ collect([$settings['shop_address'], $settings['shop_city']])->filter()->join(', ') }}
            </div>
            <div id="previewPhone" style="font-size:12px;font-weight:700;">
              @php
                $phones = collect([$settings['shop_phone'], $settings['shop_phone2']])->filter()->join(' / ');
              @endphp
              {{ $phones ? 'MOB: ' . $phones : '' }}
            </div>
          </div>
          <div class="p-3">
            <p class="text-muted small mb-2">
              <i class="bi bi-info-circle me-1"></i>
              {{ app()->getLocale() === 'ur' ? 'Yeh header har bill print par dikhega.' : 'This header appears on every printed bill.' }}
            </p>
            <a href="{{ route('settings.preview') }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100">
              <i class="bi bi-printer me-1"></i>
              {{ app()->getLocale() === 'ur' ? 'Sample Bill Dekhein' : 'Preview Sample Bill' }}
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
  // Live preview as user types
  $('input[name="shop_name"]').on('input', function () {
    $('#previewName').text($(this).val() || 'Bartan Shop');
  });
  $('input[name="shop_tagline"]').on('input', function () {
    $('#previewTagline').text($(this).val() || 'Crockery & Household Items');
  });
  $('input[name="shop_address"], input[name="shop_city"]').on('input', function () {
    var addr  = $('input[name="shop_address"]').val();
    var city  = $('input[name="shop_city"]').val();
    var parts = [addr, city].filter(Boolean);
    $('#previewAddress').text(parts.join(', '));
  });
  $('input[name="shop_phone"], input[name="shop_phone2"]').on('input', function () {
    var p1 = $('input[name="shop_phone"]').val();
    var p2 = $('input[name="shop_phone2"]').val();
    var phones = [p1, p2].filter(Boolean).join(' / ');
    $('#previewPhone').text(phones ? 'MOB: ' + phones : '');
  });
});
</script>
@endpush
