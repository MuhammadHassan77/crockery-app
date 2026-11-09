<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Login') — {{ config('app.name') }}</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="{{ asset('assets/niceadmin/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/niceadmin/css/style.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/niceadmin/css/main.css') }}" rel="stylesheet">

  <style>
    body { background: var(--body-bg, #f4f6f9); }
    .auth-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }
    .auth-card {
      width: 100%;
      max-width: 420px;
      background: var(--card-bg, #fff);
      border-radius: 1rem;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
      padding: 2.5rem 2rem;
    }
    .auth-logo {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .auth-logo img { height: 48px; }
    .auth-logo h4 { margin-top: .5rem; font-weight: 700; color: var(--primary, #4154f1); }
    .auth-logo p { color: var(--text-muted, #6c757d); font-size: .9rem; }
  </style>
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-logo">
        <img src="{{ asset('assets/niceadmin/img/logo.webp') }}" alt="{{ config('app.name') }}" style="transform: rotate(45deg)">
        <h4>{{ config('app.name') }}</h4>
        <p>@yield('auth-subtitle', 'Sign in to your account')</p>
      </div>

      @if(session('error'))
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
      @endif

      @yield('content')
    </div>
  </div>

  <script src="{{ asset('assets/niceadmin/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
