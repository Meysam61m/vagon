@extends('layouts.app')
@section('title', 'مدیریت نمایندگان')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مدیریت نمایندگان</h4>
        <a href="{{ route('representatives.create') }}" class="btn btn-primary">افزودن نماینده</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="نام، کد ملی یا تلفن">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">وضعیت</label>
                <select name="status" id="status" class="form-select">
                    <option value="">همه</option>
                    <option value="active" @selected(request('status') === 'active')>فعال</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">فیلتر</button>
                <a href="{{ route('representatives.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead>
            <tr>
                <th>نام</th>
                <th>کد ملی</th>
                <th>تلفن</th>
                <th>صاحبان کالای مرتبط</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($representatives as $representative)
                <tr>
                    <td>{{ $representative->name }}</td>
                    <td>{{ $representative->national_id ?? '-' }}</td>
                    <td>{{ $representative->phone ?? '-' }}</td>
                    <td>{{ $representative->owners->pluck('name')->join('، ') ?: '-' }}</td>
                    <td>
                        <span class="badge {{ $representative->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $representative->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('representatives.history', $representative) }}"
                           class="btn btn-sm btn-outline-info">تاریخچه</a>
                        <a href="{{ route('representatives.edit', $representative) }}"
                           class="btn btn-sm btn-outline-primary">ویرایش</a>
                        <form action="{{ route('representatives.toggle', $representative) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning" type="submit">
                                {{ $representative->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                            </button>
                        </form>
                        <form action="{{ route('representatives.destroy', $representative) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('این نماینده حذف شود؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">نماینده‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $representatives->links() }}
@endsection
