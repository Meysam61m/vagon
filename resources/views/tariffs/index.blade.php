@extends('layouts.app')
@section('title', 'مدیریت تعرفه‌ها')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مدیریت تعرفه‌ها</h4>
        <a href="{{ route('tariffs.create') }}" class="btn btn-primary">افزودن تعرفه جدید</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="کد یا عنوان تعرفه">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="equipment_type">نوع دستگاه</label>
                <select name="equipment_type" id="equipment_type" class="form-select">
                    <option value="">همه</option>
                    @foreach(\App\Models\Tariff::EQUIPMENT_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('equipment_type') === $value)>{{ $label }}</option>
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
                <a href="{{ route('tariffs.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead>
            <tr>
                <th>کد</th>
                <th>عنوان</th>
                <th>واحد</th>
                <th>ظرفیت (تن)</th>
                <th>نرخ (ریال)</th>
                <th>دسته</th>
                <th>نوع دستگاه</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tariffs as $tariff)
                <tr>
                    <td>{{ $tariff->code }}</td>
                    <td>{{ $tariff->title }}</td>
                    <td>{{ $tariff->unit }}</td>
                    <td>{{ $tariff->capacity }}</td>
                    <td class="money">{{ number_format($tariff->rate) }}</td>
                    <td>{{ $tariff->categoryLabel() }}</td>
                    <td>{{ $tariff->equipmentLabel() }}</td>
                    <td>
                        <span class="badge {{ $tariff->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $tariff->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('tariffs.edit', $tariff) }}" class="btn btn-sm btn-outline-primary">ویرایش</a>
                        <form action="{{ route('tariffs.toggle', $tariff) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning" type="submit">
                                {{ $tariff->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">تعرفه‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $tariffs->links() }}
@endsection
