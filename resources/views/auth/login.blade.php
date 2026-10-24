@extends('layouts.app')
@section('title', 'ورود به سامانه')

@push('styles')
    <style>
        .login-card {
            border-top: 4px solid #007a85 !important;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
        }

        .login-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 122, 133, 0.12);
        }

        .login-logo-box img {
            height: 72px;
            width: auto;
            object-fit: contain;
        }
    </style>
@endpush

@section('content')
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <div class="login-logo-box mb-3">
                <img src="{{ asset('images/logo.png') }}" alt="لوگوی شرکت انبارهای عمومی و خدمات گمرکی ایران">
            </div>
            <h5 class="fw-bold mb-1">سامانه مدیریت بارگیری واگن</h5>
            <div class="text-muted small">شرکت انبارهای عمومی و خدمات گمرکی ایران — مجتمع شهید مطهری</div>
        </div>

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">ایمیل</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">رمز عبور</label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember" checked>
                <label class="form-check-label" for="remember">مرا به خاطر بسپار (حفظ نشست در طول شیفت کاری)</label>
            </div>
            <button class="btn btn-primary w-100" type="submit">ورود</button>
        </form>
    </div>
@endsection
