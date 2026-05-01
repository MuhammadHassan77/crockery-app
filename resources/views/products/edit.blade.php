@extends('layouts.app')
@section('title', __('Edit Product') . ': ' . $product->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Edit Product') }}</h4>
    <small class="text-muted">{{ $product->name }}</small>
  </div>
  <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
      @csrf @method('PUT')

      <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Basic Information') }}</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Product Name') }} <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name', $product->name) }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">اردو نام <small class="text-muted fw-normal">(Urdu Name)</small></label>
              <input type="text" name="name_ur" class="form-control @error('name_ur') is-invalid @enderror"
                     value="{{ old('name_ur', $product->name_ur) }}" placeholder="مثلاً: سٹیل پلیٹ" dir="rtl">
              @error('name_ur')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <small class="text-muted">Invoice Urdu print ke liye</small>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                  </option>
                @endforeach
              </select>
              @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">{{ __('Description') }}</label>
              <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="col-md-4">
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                       {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="isActive">{{ app()->getLocale() === 'ur' ? 'Maal Chaloo Hai' : 'Product Active' }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-check-lg me-1"></i> {{ __('Save Changes') }}
        </button>
        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><h6 class="mb-0">{{ __('Product Image') }}</h6></div>
      <div class="card-body text-center">
        <div id="imagePreview" class="mb-3">
          @if($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid rounded" style="max-height:160px;">
          @else
            <div class="text-muted py-4" style="background:#f8f9fa;border-radius:.5rem;">
              <i class="bi bi-image fs-2 d-block"></i><small>{{ app()->getLocale() === 'ur' ? 'Koi tasveer nahi' : 'No image' }}</small>
            </div>
          @endif
        </div>
        <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
        <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Khaali chhodein purani tasveer rakhne k liye.' : 'Leave blank to keep current image.' }}</small>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $('#imageInput').on('change', function () {
    var file = this.files[0];
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $('#imagePreview').html('<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height:160px;">');
      };
      reader.readAsDataURL(file);
    }
  });
});
</script>
@endpush
