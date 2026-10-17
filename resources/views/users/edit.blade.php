@extends('layouts.app')
@section('title', 'ویرایش کاربر')

@section('content')
    <h4 class="mb-3">ویرایش کاربر: {{ $user->name }}</h4>

    <form method="POST" action="{{ route('users.update', $user) }}" class="card p-4" style="max-width: 680px;">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">نام</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">ایمیل</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" dir="ltr"
                       class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">تلفن</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">نقش</label>
                <select name="role" id="role" class="form-select" required
                        @disabled($user->id === auth()->id())>
                    @foreach(\App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if($user->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <div class="form-text">نقش حساب خودتان قابل تغییر نیست.</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">رمز عبور جدید (اختیاری)</label>
                <input type="password" name="password" id="password" dir="ltr" class="form-control"
                       autocomplete="new-password">
                <div class="form-text">اگر خالی بماند، رمز فعلی تغییر نمی‌کند.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">تکرار رمز عبور جدید</label>
                <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr"
                       class="form-control" autocomplete="new-password">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">بروزرسانی</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection
