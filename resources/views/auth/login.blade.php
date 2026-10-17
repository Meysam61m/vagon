@extends('layouts.app')
@section('title', 'ورود به سامانه')

@section('content')
    <div class="card login-card p-4">
        <h5 class="mb-4 text-center">ورود به سامانه مدیریت بارگیری واگن</h5>

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
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
            </div>
            <button class="btn btn-primary w-100" type="submit">ورود</button>
        </form>
    </div>
@endsection
