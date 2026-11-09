@extends('layouts.app')
@section('title', __('Add Product'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">{{ __('Add Product') }}</h4>
    <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Maal ki Malumat Bharein' : 'Fill in the product details below' }}</small>
  </div>
  <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
      @csrf

      <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Basic Information') }}</h6></div>
        <div class="card-body">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Product Name') }} <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('name') }}" placeholder="e.g. Steel Dinner Plate" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">اردو نام <small class="text-muted fw-normal">(Urdu Name)</small></label>
              <input type="text" name="name_ur" class="form-control @error('name_ur') is-invalid @enderror"
                     value="{{ old('name_ur') }}" placeholder="مثلاً: سٹیل پلیٹ" dir="rtl">
              @error('name_ur')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <small class="text-muted">Invoice Urdu print ke liye</small>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                <option value="">{{ app()->getLocale() === 'ur' ? '-- Qism Chunein --' : '-- Select Category --' }}</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                  </option>
                @endforeach
              </select>
              @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="mt-1">
                <a href="#" data-bs-toggle="modal" data-bs-target="#newCategoryModal" class="small">
                  <i class="bi bi-plus-circle me-1"></i>{{ app()->getLocale() === 'ur' ? 'Nai Qism Daalo' : 'Add new category' }}
                </a>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="1" id="is_weighted" name="is_weighted">
                <label class="form-check-label small" for="is_weighted">
                  {{ app()->getLocale() === 'ur' ? 'Kya ye Wazan wali product he?' : 'Is Weighted Product?' }}
                </label>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">{{ __('Description') }}</label>
              <textarea name="description" class="form-control" rows="3"
                        placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari tafseel...' : 'Optional product description...' }}">{{ old('description') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Sizes + Prices --}}
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="mb-0">{{ __('Sizes & Prices') }}</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addSizeRow">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add Size') }}
          </button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0" id="sizesTable">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Size Label') }} <span class="text-danger">*</span></th>
                  <th>سائز (اردو)</th>
                  <th>{{ __('SKU') }}</th>
                  <th>{{ __('Opening Stock') }}</th>
                  <th>{{ __('Low Stock Alert') }}</th>
                  <th>{{ __('Purchase Price (PKR)') }} </th>
                  <th>{{ __('Price (PKR)') }} <span class="text-danger">*</span></th>
                  <th width="50"></th>
                </tr>
              </thead>
              <tbody id="sizeRows">
                <tr class="size-row">
                  <td><input type="text" name="sizes[0][size_label]" class="form-control form-control-sm" placeholder="e.g. Small / 6-inch" required></td>
                  <td><input type="text" name="sizes[0][size_label_ur]" class="form-control form-control-sm" placeholder="مثلاً: چھوٹا" dir="rtl"></td>
                  <td><input type="text" name="sizes[0][sku]" class="form-control form-control-sm" placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari' : 'Optional' }}"></td>
                  <td><input type="number" name="sizes[0][stock_quantity]" class="form-control form-control-sm" value="0" min="0"></td>
                  <td><input type="number" name="sizes[0][low_stock_threshold]" class="form-control form-control-sm" value="5" min="0"></td>
                  <td><input type="number" name="sizes[0][purchase_price]" class="form-control form-control-sm" placeholder="0" min="0"></td>
                  <td><input type="number" name="sizes[0][initial_price]" class="form-control form-control-sm" placeholder="0" min="0" required></td>
                  <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-size"><i class="bi bi-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Kam az kam ek size zaroori hai. Qeemat price history mein mehfooz hogi.' : 'At least one size is required. Price will be stored in price history.' }}</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-check-lg me-1"></i> {{ __('Save Product') }}
        </button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0">{{ __('Product Image') }}</h6></div>
      <div class="card-body text-center">
        <div id="imagePreview" class="mb-3" style="min-height:120px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;border-radius:.5rem;border:2px dashed #dee2e6;">
          <div class="text-muted">
            <i class="bi bi-image fs-2 d-block mb-1"></i>
            <small>{{ app()->getLocale() === 'ur' ? 'Koi tasveer nahi' : 'No image selected' }}</small>
          </div>
        </div>
        <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/*">
        <small class="text-muted">{{ app()->getLocale() === 'ur' ? 'Zyada se zyada 2MB. JPG, PNG, WebP.' : 'Max 2MB. JPG, PNG, WebP.' }}</small>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h6 class="mb-0">{{ app()->getLocale() === 'ur' ? 'Masharay' : 'Tips' }}</h6></div>
      <div class="card-body small text-muted">
        <ul class="ps-3 mb-0">
          @if(app()->getLocale() === 'ur')
            <li>Ek maal ke kai sizes ho sakte hain (maslan 6-inch, 8-inch, 10-inch)</li>
            <li>Har size ki apni qeemat aur stor hai</li>
            <li>Qeemat ki tabdeeliyan price history mein mehfooz hoti hain</li>
          @else
            <li>You can add multiple sizes per product (e.g. 6-inch, 8-inch, 10-inch)</li>
            <li>Each size has its own price and stock</li>
            <li>Price changes are logged in price history</li>
          @endif
        </ul>
      </div>
    </div>
  </div>
</div>

{{-- New Category Modal --}}
<div class="modal fade" id="newCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">{{ app()->getLocale() === 'ur' ? 'Nai Qism' : 'New Category' }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label fw-semibold">{{ app()->getLocale() === 'ur' ? 'Qism ka Naam' : 'Category Name' }}</label>
        <input type="text" id="newCategoryName" class="form-control" placeholder="e.g. Dinner Sets">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-sm btn-primary" id="saveCategoryBtn">{{ __('Save') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  var sizeIndex = 1;

  $('#addSizeRow').on('click', function () {
    var row = `
      <tr class="size-row">
        <td><input type="text" name="sizes[${sizeIndex}][size_label]" class="form-control form-control-sm" placeholder="e.g. Medium / 8-inch" required></td>
        <td><input type="text" name="sizes[${sizeIndex}][size_label_ur]" class="form-control form-control-sm" placeholder="مثلاً: درمیانہ" dir="rtl"></td>
        <td><input type="text" name="sizes[${sizeIndex}][sku]" class="form-control form-control-sm" placeholder="{{ app()->getLocale() === 'ur' ? 'Ikhtiyari' : 'Optional' }}"></td>
        <td><input type="number" name="sizes[${sizeIndex}][stock_quantity]" class="form-control form-control-sm" value="0" min="0"></td>
        <td><input type="number" name="sizes[${sizeIndex}][low_stock_threshold]" class="form-control form-control-sm" value="5" min="0"></td>
        <td><input type="number" name="sizes[${sizeIndex}][purchase_price]" class="form-control form-control-sm" placeholder="0" min="0"></td>
        <td><input type="number" name="sizes[${sizeIndex}][initial_price]" class="form-control form-control-sm" placeholder="0" min="0" required></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-size"><i class="bi bi-trash"></i></button></td>
      </tr>`;
    $('#sizeRows').append(row);
    sizeIndex++;
  });

  $(document).on('click', '.remove-size', function () {
    if ($('.size-row').length > 1) {
      $(this).closest('tr').remove();
    } else {
      alert('{{ app()->getLocale() === 'ur' ? 'Kam az kam ek size zaroori hai.' : 'At least one size is required.' }}');
    }
  });

  $('#imageInput').on('change', function () {
    var file = this.files[0];
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $('#imagePreview').html('<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height:180px;">');
      };
      reader.readAsDataURL(file);
    }
  });

  $('#saveCategoryBtn').on('click', function () {
    var name = $.trim($('#newCategoryName').val());
    if (!name) { alert('{{ app()->getLocale() === 'ur' ? 'Qism ka naam likhein.' : 'Please enter a category name.' }}'); return; }

    $.post('{{ route('categories.store') }}', { name: name }, function (res) {
      $('select[name="category_id"]').append(
        $('<option>', { value: res.id, text: res.name, selected: true })
      );
      $('#newCategoryName').val('');
      $('#newCategoryModal').modal('hide');
    }).fail(function () {
      alert('{{ app()->getLocale() === 'ur' ? 'Qism mehfooz nahi ho saki. Dobara koshish karein.' : 'Could not save category. Please try again.' }}');
    });
  });
});
</script>
@endpush
