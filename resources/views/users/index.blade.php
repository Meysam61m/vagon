@extends('layouts.app')
@section('title', 'مدیریت کاربران')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مدیریت کاربران</h4>
        <a href="{{ route('users.create') }}" class="btn btn-primary">افزودن کاربر</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="نام، ایمیل یا تلفن">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="role">نقش</label>
                <select name="role" id="role" class="form-select">
                    <option value="">همه</option>
                    @foreach(\App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">وضعیت</label>
                <select name="status" id="status" class="form-select">
                    <option value="">همه</option>
                    <option value="active" @selected(request('status') === 'active')>فعال</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">فیلتر</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead>
            <tr>
                <th>نام</th>
                <th>ایمیل</th>
                <th>تلفن</th>
                <th>نقش</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>{{ $user->roleLabel() }}</td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('users.toggle', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-warning" type="submit">
                                    {{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('این کاربر حذف شود؟ سوابق صورتحساب‌های او حفظ می‌شود.');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                            </form>
                        @else
                            <span class="text-muted small">حساب شما</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">کاربری یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
