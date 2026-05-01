<header class="header">
  <div class="header-left">
    <button class="sidebar-toggle" title="Toggle Sidebar" aria-label="Toggle sidebar">
      <span class="menu-lines" aria-hidden="true">
        <span></span><span></span><span></span>
      </span>
    </button>
    <a href="{{ route('dashboard') }}" class="header-brand" aria-label="Home">
      <span class="header-logo">
        <img src="{{ asset('assets/niceadmin/img/logo.webp') }}" alt="{{ config('app.name') }}">
      </span>
      <span class="header-context">
        <strong class="header-context-title">{{ config('app.name') }}</strong>
      </span>
    </a>
  </div>

  {{-- Global Search --}}
  <div class="header-search-wrap" id="globalSearchWrap">
    <div class="search-form position-relative">
      <i class="bi bi-search search-icon"></i>
      <input type="search" id="globalSearchInput"
             placeholder="{{ app()->getLocale() === 'ur' ? 'Raseed, Grahak, Maal dhundo...' : 'Search invoices, customers, products...' }}"
             autocomplete="off" spellcheck="false">
      <kbd class="search-shortcut">/</kbd>
    </div>
    {{-- Results dropdown --}}
    <div id="searchResults" class="search-dropdown" style="display:none">
      <div id="searchResultsInner"></div>
    </div>
  </div>

  <div class="header-right">
    <div class="header-actions-desktop">

      {{-- Low stock alert --}}
      @php
        $lowStockCount = \App\Models\ProductSize::whereColumn('stock_quantity','<=','low_stock_threshold')->count();
      @endphp
      @if($lowStockCount > 0)
      <div class="header-action-wrap dropdown notification-dropdown">
        <button class="header-action dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Low Stock Alerts">
          <i class="bi bi-box-seam"></i>
          <span class="header-badge">{{ $lowStockCount }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end" style="min-width:260px;">
          <div class="p-3 border-bottom fw-semibold text-warning">
            <i class="bi bi-exclamation-triangle me-1"></i> Low Stock Items
          </div>
          <div class="p-2">
            @foreach(\App\Models\ProductSize::with('product')->whereColumn('stock_quantity','<=','low_stock_threshold')->take(5)->get() as $s)
              <a class="dropdown-item py-2" href="{{ route('products.show', $s->product_id) }}">
                <div class="d-flex justify-content-between">
                  <span>{{ $s->product->name }} ({{ $s->size_label }})</span>
                  <span class="badge bg-danger">{{ $s->stock_quantity }} left</span>
                </div>
              </a>
            @endforeach
            @if($lowStockCount > 5)
              <a class="dropdown-item text-center text-primary pt-2" href="{{ route('reports.stock') }}">
                View all {{ $lowStockCount }} items
              </a>
            @endif
          </div>
        </div>
      </div>
      @endif

      {{-- Pending payments alert --}}
      @php
        $pendingCount = \App\Models\Invoice::whereIn('status',['pending','partial'])->count();
      @endphp
      @if($pendingCount > 0)
      <div class="header-action-wrap">
        <a href="{{ route('invoices.index', ['status' => 'pending']) }}" class="header-action position-relative" title="Pending Payments">
          <i class="bi bi-cash-coin"></i>
          <span class="header-badge">{{ $pendingCount }}</span>
        </a>
      </div>
      @endif

      {{-- Language toggle --}}
      <div class="header-action-wrap">
        @if(app()->getLocale() === 'ur')
          <a href="{{ route('language.switch', 'en') }}" class="header-action d-flex align-items-center gap-1 text-decoration-none" title="Switch to English" style="font-size:12px;font-weight:700;padding:4px 8px;border-radius:6px;border:1.5px solid currentColor;opacity:.7">
            EN
          </a>
        @else
          <a href="{{ route('language.switch', 'ur') }}" class="header-action d-flex align-items-center gap-1 text-decoration-none" title="Roman Urdu mein Badlo" style="font-size:12px;font-weight:700;padding:4px 8px;border-radius:6px;border:1.5px solid currentColor;opacity:.7">
            UR
          </a>
        @endif
      </div>

      {{-- Dark / Light toggle --}}
      <button class="header-action theme-toggle" title="{{ __('Toggle Theme') }}">
        <i class="ph-light ph-moon-stars theme-icon-dark"></i>
        <i class="ph-light ph-sun theme-icon-light"></i>
      </button>

      {{-- User dropdown --}}
      <div class="header-action-wrap dropdown user-dropdown">
        <button class="dropdown-toggle user-trigger" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="{{ asset('assets/niceadmin/img/profile-img.webp') }}" alt="User" class="user-avatar">
          <div class="user-brief">
            <span class="user-name">{{ auth()->user()->name }}</span>
            <span class="user-role">{{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}</span>
          </div>
        </button>
        <div class="dropdown-menu dropdown-menu-end user-menu">
          <div class="user-menu-header">
            <img src="{{ asset('assets/niceadmin/img/profile-img.webp') }}" alt="User" class="user-menu-avatar">
            <div class="user-menu-info">
              <div class="user-menu-name">{{ auth()->user()->name }}</div>
              <div class="user-menu-email">{{ auth()->user()->email }}</div>
            </div>
          </div>
          <div class="user-menu-footer">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="user-menu-logout w-100 border-0 bg-transparent text-start">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>

    {{-- Mobile actions --}}
    <div class="header-actions-mobile">
      <button class="header-action search-toggle" title="Search">
        <i class="bi bi-search"></i>
      </button>
      <button class="header-action mobile-menu-toggle" title="Menu">
        <i class="bi bi-three-dots"></i>
      </button>
    </div>
  </div>
</header>

{{-- Search dropdown styles --}}
<style>
#globalSearchWrap { position: relative; }
.search-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  z-index: 9999;
  max-height: 420px;
  overflow-y: auto;
}
.search-group-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #999;
  padding: 10px 14px 4px;
}
.search-result-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  text-decoration: none;
  color: inherit;
  transition: background .15s;
}
.search-result-item:hover, .search-result-item.active {
  background: #f5f7ff;
  color: inherit;
}
.search-result-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.search-icon-customer { background: #e8f4fd; color: #0d6efd; }
.search-icon-invoice  { background: #fff3e0; color: #fd7e14; }
.search-icon-product  { background: #e8f8f0; color: #198754; }
.search-result-text { flex: 1; min-width: 0; }
.search-result-label  { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-result-sub    { font-size: 11px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-empty { padding: 20px; text-align: center; color: #aaa; font-size: 13px; }
</style>

@push('scripts')
<script>
$(function () {
  var searchUrl = '{{ route('search') }}';
  var $input    = $('#globalSearchInput');
  var $results  = $('#searchResults');
  var $inner    = $('#searchResultsInner');
  var timer, activeIdx = -1;

  $(document).on('keydown', function (e) {
    if (e.key === '/' && !$(e.target).is('input,textarea,select')) {
      e.preventDefault();
      $input.focus();
    }
    if (e.key === 'Escape') closeResults();
  });

  $input.on('input', function () {
    clearTimeout(timer);
    var q = $.trim($(this).val());
    if (q.length < 2) { closeResults(); return; }
    timer = setTimeout(function () { doSearch(q); }, 250);
  });

  $input.on('keydown', function (e) {
    var $items = $results.find('.search-result-item');
    if (!$items.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIdx = Math.min(activeIdx + 1, $items.length - 1);
      $items.removeClass('active').eq(activeIdx).addClass('active');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIdx = Math.max(activeIdx - 1, 0);
      $items.removeClass('active').eq(activeIdx).addClass('active');
    } else if (e.key === 'Enter') {
      var $a = $items.filter('.active');
      if ($a.length) { e.preventDefault(); window.location.href = $a.attr('href'); }
    }
  });

  function doSearch(q) {
    $.get(searchUrl, { q: q }, function (data) {
      activeIdx = -1;
      if (!data.results || !data.results.length) {
        $inner.html('<div class="search-empty"><i class="bi bi-search me-1"></i>No results for "<strong>' + esc(q) + '</strong>"</div>');
        $results.show();
        return;
      }
      var groups    = { customer: [], invoice: [], product: [] };
      var labels    = { customer: 'Customers', invoice: 'Invoices', product: 'Products' };
      var iconClass = { customer: 'search-icon-customer', invoice: 'search-icon-invoice', product: 'search-icon-product' };
      $.each(data.results, function (_, r) { if (groups[r.type]) groups[r.type].push(r); });
      var html = '';
      $.each(groups, function (type, items) {
        if (!items.length) return;
        html += '<div class="search-group-label">' + labels[type] + '</div>';
        $.each(items, function (_, r) {
          var badge = r.badge ? ' <span class="badge bg-' + r.badge.color + ' ms-1" style="font-size:10px">' + esc(r.badge.text) + '</span>' : '';
          html += '<a href="' + esc(r.url) + '" class="search-result-item">' +
            '<div class="search-result-icon ' + iconClass[type] + '"><i class="bi ' + r.icon + '"></i></div>' +
            '<div class="search-result-text">' +
              '<div class="search-result-label">' + esc(r.label) + badge + '</div>' +
              '<div class="search-result-sub">' + esc(r.sublabel) + '</div>' +
            '</div></a>';
        });
      });
      $inner.html(html);
      $results.show();
    });
  }

  function closeResults() { $results.hide(); $inner.html(''); activeIdx = -1; }
  function esc(str) { return $('<div>').text(str).html(); }

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#globalSearchWrap').length) closeResults();
  });

  $input.on('focus', function () {
    if ($.trim($(this).val()).length >= 2) $(this).trigger('input');
  });
});
</script>
@endpush
