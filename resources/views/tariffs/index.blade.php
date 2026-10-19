@extends('layouts.app')
@section('title', 'مدیریت تعرفه‌ها')

@section('content')
    @php
        $taxPct = $taxPercentage ?? 10;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">مدیریت تعرفه‌ها (کتابچه تعرفه عملیات بارگیری و تخلیه)</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('tariffs.create') }}" class="btn btn-primary">افزودن تعرفه جدید</a>
            <a href="{{ route('tariffs.index', array_merge(request()->query(), ['export' => 'excel'])) }}"
               class="btn btn-success">
                خروجی اکسل تعرفه‌ها (tarefe.xlsx)
            </a>
        </div>
    </div>

    <div class="card p-3 mb-3 border-success">
        <form method="POST"
              action="{{ \Illuminate\Support\Facades\Route::has('tariffs.import') ? route('tariffs.import') : url('/tariffs/import') }}"
              enctype="multipart/form-data"
              class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label fw-bold" for="tariff_file">
                    بارگذاری و بروزرسانی تعرفه‌ها از فایل اکسل (<code>tarefe.xlsx</code>)
                </label>
                <input type="file"
                       name="tariff_file"
                       id="tariff_file"
                       accept=".xlsx"
                       class="form-control @error('tariff_file') is-invalid @enderror"
                       required>
                @error('tariff_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    فایل اکسل تعرفه (<code>.xlsx</code>) را انتخاب کنید تا کد، عنوان کالا/تعرفه، وسیله حمل (جرثقیل/لیفتراک/کارگری)، ظرفیت و نرخ کتابچه به‌صورت خودکار ثبت شود.
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success">
                    بارگذاری فایل اکسل تعرفه
                </button>
            </div>
        </form>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="کد یا عنوان تعرفه / نام کالا">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="equipment_type">نوع دستگاه (وسیله حمل)</label>
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
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead>
            <tr>
                <th>کد</th>
                <th>عنوان تعرفه / کالا</th>
                <th>واحد</th>
                <th>ظرفیت (تن)</th>
                <th>مبلغ تعرفه در کتابچه (ریال)</th>
                <th>مبلغ با احتساب مالیات ({{ $taxPct }}٪)</th>
                <th>نرخ هر تن در کتابچه (ریال)</th>
                <th>دسته</th>
                <th>نوع دستگاه</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tariffs as $tariff)
                @php
                    $withVat = (int) round($tariff->rate * (1 + ($taxPct / 100)));
                    $perTon = (float) $tariff->capacity > 0 ? (int) round($tariff->rate / (float) $tariff->capacity) : 0;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $tariff->code }}</td>
                    <td>{{ $tariff->title }}</td>
                    <td>{{ $tariff->unit }}</td>
                    <td>{{ $tariff->capacity }}</td>
                    <td class="money fw-bold" style="background-color: rgba(146, 208, 80, 0.25);">
                        {{ number_format($tariff->rate) }}
                    </td>
                    <td class="money fw-bold text-primary">{{ number_format($withVat) }}</td>
                    <td class="money text-muted">{{ number_format($perTon) }}</td>
                    <td>{{ $tariff->categoryLabel() }}</td>
                    <td>
                        <span class="badge bg-info text-dark">{{ $tariff->equipmentLabel() }}</span>
                    </td>
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
                        <form action="{{ \Illuminate\Support\Facades\Route::has('tariffs.destroy') ? route('tariffs.destroy', $tariff) : url('/tariffs/'.$tariff->id) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('آیا از حذف تعرفه «{{ $tariff->title }}» اطمینان دارید؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted py-4">تعرفه‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $tariffs->links() }}
@endsection
