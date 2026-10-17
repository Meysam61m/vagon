@extends('layouts.app')
@section('title', 'افزودن کاربر')

@section('content')
    <h4 class="mb-3">افزودن کاربر جدید</h4>

    <form method="POST" action="{{ route('users.store') }}" class="card p-4" style="max-width: 680px;">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">نام</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">ایمیل</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" dir="ltr"
                       class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">تلفن</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">نقش</label>
                <select name="role" id="role" class="form-select" required>
                    @foreach(\App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', 'operator') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">رمز عبور (حداقل ۸ کاراکتر)</label>
                <input type="password" name="password" id="password" dir="ltr"
                       class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">تکرار رمز عبور</label>
                <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr"
                       class="form-control" required autocomplete="new-password">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection
