@extends('layouts.app')
@section('title', __('Edit Vendor'))

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ __('Edit Vendor') }}</h4>
            <small class="text-muted">{{ $vendor->name }}</small>
        </div>
        <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vendors.update', $vendor) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Full Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $vendor->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Phone') }}</label>
                                <input type="text" name="phone" class="form-control"
                                    value="{{ old('phone', $vendor->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Email') }}</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $vendor->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('City') }}</label>
                                <input type="text" name="city" class="form-control"
                                    value="{{ old('city', $vendor->city) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('Address') }}</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $vendor->address) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $vendor->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">{{ __('Active Vendor') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> {{ __('Save Changes') }}
                            </button>
                            <a href="{{ route('vendors.show', $vendor) }}"
                                class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
