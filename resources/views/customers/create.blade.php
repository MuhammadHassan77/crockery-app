@extends('layouts.app')
@section('title', __('Add Customer'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Add Customer') }}</h4>
    <small class="text-muted">{{ __('Manage your regular customers') }}</small>
  </div>
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
  </a>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('customers.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Full Name') }} <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" placeholder="{{ app()->getLocale() === 'ur' ? 'Grahak ka Naam' : 'Customer name' }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Phone') }}</label>
              <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                     value="{{ old('phone') }}" placeholder="03xx-xxxxxxx">
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Email') }}</label>
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                     value="{{ old('email') }}" placeholder="{{ app()->getLocale() === 'ur' ? 'Zaroor Nahi' : 'Optional' }}">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('City') }}</label>
              <input type="text" name="city" class="form-control" value="{{ old('city') }}"
                     placeholder="{{ app()->getLocale() === 'ur' ? 'Zaroor Nahi' : 'Optional' }}">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">{{ __('Address') }}</label>
              <textarea name="address" class="form-control" rows="2"
                        placeholder="{{ app()->getLocale() === 'ur' ? 'Zaroor Nahi' : 'Optional' }}">{{ old('address') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">{{ __('Notes') }}</label>
              <textarea name="notes" class="form-control" rows="2"
                        placeholder="{{ app()->getLocale() === 'ur' ? 'Koi Khas Baat...' : 'Any special notes about this customer...' }}">{{ old('notes') }}</textarea>
            </div>
          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i> {{ __('Save Customer') }}
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
