<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&family=Poppins:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">

  <!-- Local Vendor CSS -->
  <link href="{{ asset('assets/niceadmin/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/niceadmin/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/niceadmin/css/choices.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/niceadmin/css/flatpickr.min.css') }}" rel="stylesheet">

  <!-- Icon Fonts via CDN (font files not bundled in free download) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/light/style.css" rel="stylesheet">

  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

  <!-- Select2 CSS for searchable selects -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

  <!-- Template Main CSS -->
  <link href="{{ asset('assets/niceadmin/css/main.css') }}" rel="stylesheet">

  <style>
    /* DataTables inside cards fix */
    .dataTables_wrapper .dataTables_filter input { border: 1px solid var(--border-color,#dee2e6); border-radius:.375rem; padding:.3rem .6rem; }
    .dataTables_wrapper .dataTables_length select { border: 1px solid var(--border-color,#dee2e6); border-radius:.375rem; padding:.2rem .5rem; }
    table.dataTable thead th { border-bottom: 2px solid var(--border-color,#dee2e6); }
  </style>

  @stack('styles')
</head>
<body>

  {{-- ===== HEADER ===== --}}
  @include('_partials.header')

  {{-- ===== SIDEBAR ===== --}}
  @include('_partials.sidebar')

  <!-- Sidebar Overlay (Mobile) -->
  <div class="sidebar-overlay"></div>

  {{-- ===== MAIN CONTENT ===== --}}
  <main class="main">
    <div class="main-content">

      {{-- Page Header / Breadcrumb --}}
      @hasSection('page-header')
        <div class="pagetitle">
          <h1>@yield('page-title')</h1>
          <nav>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
              @yield('breadcrumb')
            </ol>
          </nav>
        </div>
      @endif

      {{-- Flash Messages --}}
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif

      @yield('content')

    </div>
    @include('_partials.footer')
  </main>

  <!-- Core JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="{{ asset('assets/niceadmin/js/bootstrap.bundle.min.js') }}"></script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

  <!-- Select2 JS for searchable selects -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script src="{{ asset('assets/niceadmin/js/choices.min.js') }}"></script>
  <script src="{{ asset('assets/niceadmin/js/flatpickr.min.js') }}"></script>
  <script src="{{ asset('assets/niceadmin/js/theme.js') }}"></script>
  <script src="{{ asset('assets/niceadmin/js/main.js') }}"></script>
  <script src="{{ asset('assets/niceadmin/js/apps-sidebar-toggle.js') }}"></script> 
   <script src="{{ asset('js/select2-helpers.js') }}"></script>
  <script>
    // Global AJAX CSRF setup
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Auto-dismiss alerts after 4s
    setTimeout(function () { $('.alert.alert-success, .alert.alert-danger').fadeOut(400); }, 4000);
  </script>

  @stack('scripts')
</body>
</html>
