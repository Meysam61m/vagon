@extends('layouts.app')
@section('title', 'مدیریت صاحبان کالا')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مدیریت صاحبان کالا</h4>
        <a href="{{ route('owners.create') }}" class="btn btn-primary">افزودن صاحب کالا</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="نام، کد ملی، تلفن یا کد اقتصادی">
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
                <a href="{{ route('owners.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
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
                <th>کد اقتصادی</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($owners as $owner)
                <tr>
                    <td>{{ $owner->name }}</td>
                    <td>{{ $owner->national_id ?? '-' }}</td>
                    <td>{{ $owner->phone ?? '-' }}</td>
                    <td>{{ $owner->economic_code ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $owner->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $owner->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('owners.history', $owner) }}" class="btn btn-sm btn-outline-info">تاریخچه</a>
                        <a href="{{ route('owners.edit', $owner) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                        <form action="{{ route('owners.toggle', $owner) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning" type="submit">
                                {{ $owner->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                            </button>
                        </form>
                        <form action="{{ route('owners.destroy', $owner) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('این صاحب کالا حذف شود؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">صاحب کالایی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $owners->links() }}
@endsection
