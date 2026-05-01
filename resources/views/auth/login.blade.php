@extends('layouts.auth')

@section('title', __('Login'))
@section('auth-subtitle', app()->getLocale() === 'ur' ? 'Apna Email aur Password Dalein' : 'Enter your credentials to continue')

@section('content')

{{-- Language toggle on login page --}}
<div class="text-end mb-3">
  @if(app()->getLocale() === 'ur')
    <a href="{{ route('language.switch', 'en') }}" class="btn btn-sm btn-outline-secondary">English</a>
  @else
    <a href="{{ route('language.switch', 'ur') }}" class="btn btn-sm btn-outline-secondary">Roman Urdu</a>
  @endif
</div>

<form method="POST" action="{{ route('login') }}" novalidate>
  @csrf

  <div class="mb-3">
    <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
      <input type="email" id="email" name="email"
             class="form-control @error('email') is-invalid @enderror"
             value="{{ old('email') }}" placeholder="admin@bartanshop.com" autofocus required>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="mb-3">
    <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" id="password" name="password"
             class="form-control @error('password') is-invalid @enderror"
             placeholder="••••••••" required>
      <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1">
        <i class="bi bi-eye" id="pwdIcon"></i>
      </button>
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="mb-4">
    <div class="form-check mb-0">
      <input class="form-check-input" type="checkbox" name="remember" id="remember">
      <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
    </div>
  </div>

  <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
    <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign In') }}
  </button>
</form>

<script>
  document.getElementById('togglePwd').addEventListener('click', function () {
    var pwd  = document.getElementById('password');
    var icon = document.getElementById('pwdIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      pwd.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  });
</script>
@endsection
