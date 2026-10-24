@extends('layouts.app')
@section('title', 'گزارش در انتظار بارگیری و صورتحساب‌نشده')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">گزارش واگن‌های در انتظار بارگیری و صورتحساب‌نشده</h4>
            <div class="text-muted small">
                لیست کلیه واگن‌های باز شامل واگن‌های <strong>در انتظار ثبت بارگیری</strong> و واگن‌های <strong>بارگیری‌شده‌ای که هنوز صورتحساب برای آن‌ها صادر نشده است</strong>.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap no-print">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success btn-sm fw-semibold">
                دریافت خروجی اکسل (Excel)
            </a>
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="window.print()">
                چاپ گزارش
            </button>
            <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary btn-sm">
                کارتابل عملیات بارگیری
            </a>
        </div>
    </div>

    <form method="GET" action="{{ \Illuminate\Support\Facades\Route::has('reports.pending-loading') ? route('reports.pending-loading') : url('/reports/pending-loading') }}" class="card p-3 mb-3 no-print">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="pending_search">جستجو (شماره واگن، درخواست، شرح کالا، خط)</label>
                <input type="text" name="search" id="pending_search" value="{{ request('search') }}" class="form-control" placeholder="مثلاً شماره واگن یا خط…">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="pending_stage">وضعیت واگن</label>
                <select name="stage" id="pending_stage" class="form-select">
                    <option value="">همه (در انتظار بارگیری + صورتحساب‌نشده)</option>
                    <option value="pending_loading" @selected(request('stage') === 'pending_loading')>فقط در انتظار بارگیری</option>
                    <option value="unbilled" @selected(request('stage') === 'unbilled')>فقط بارگیری‌شده (صورتحساب‌نشده)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="pending_owner_id">صاحب کالا</label>
                <select name="owner_id" id="pending_owner_id" class="form-select">
                    <option value="">همه صاحبان کالا</option>
                    <option value="unknown" @selected(request('owner_id') === 'unknown')>— ناشناس —</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected(request('owner_id') !== 'unknown' && (int) request('owner_id') === $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="pending_rep_id">نماینده</label>
                <select name="representative_id" id="pending_rep_id" class="form-select">
                    <option value="">همه نمایندگان</option>
                    @foreach($representatives as $rep)
                        <option value="{{ $rep->id }}" @selected((int) request('representative_id') === $rep->id)>{{ $rep->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label" for="pending_from">از تاریخ</label>
                <input type="text" name="from" id="pending_from" value="{{ $fromShamsi }}" class="form-control jalali-datepicker" autocomplete="off" placeholder="همه">
            </div>
            <div class="col-md-1">
                <label class="form-label" for="pending_to">تا تاریخ</label>
                <input type="text" name="to" id="pending_to" value="{{ $toShamsi }}" class="form-control jalali-datepicker" autocomplete="off" placeholder="همه">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button class="btn btn-primary w-100" type="submit">اعمال</button>
                @if(request()->hasAny(['search', 'stage', 'owner_id', 'representative_id', 'from', 'to']))
                    <a href="{{ \Illuminate\Support\Facades\Route::has('reports.pending-loading') ? route('reports.pending-loading') : url('/reports/pending-loading') }}" class="btn btn-outline-secondary" title="پاک کردن فیلترها">×</a>
                @endif
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center border-primary">
                <div class="kpi-value text-primary">{{ number_format($summary['total_wagons'] ?? 0) }}</div>
                <div class="text-muted small">کل واگن‌های باز (بارگیری‌نشده + صورتحساب‌نشده)</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center border-warning">
                <div class="kpi-value text-warning-emphasis">{{ number_format($summary['pending_loading_wagons'] ?? 0) }}</div>
                <div class="text-muted small">واگن‌های در انتظار بارگیری</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center border-info">
                <div class="kpi-value text-info-emphasis">{{ number_format($summary['unbilled_wagons'] ?? 0) }}</div>
                <div class="text-muted small">واگن‌های بارگیری‌شده (صورتحساب‌نشده)</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary['total_requests'] ?? 0) }}</div>
                <div class="text-muted small">تعداد درخواست‌های باز ({{ number_format($summary['unknown_owner_wagons'] ?? 0) }} واگن ناشناس)</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>ردیف</th>
                    <th>شماره درخواست</th>
                    <th>تاریخ درخواست</th>
                    <th>شماره واگن</th>
                    <th>شرح کالا</th>
                    <th>شماره خط</th>
                    <th>صاحب کالا</th>
                    <th>نماینده</th>
                    <th>تعرفه ثبت‌شده</th>
                    <th>وزن (تن)</th>
                    <th>وضعیت واگن</th>
                    <th class="no-print">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($wagons as $wagon)
                    @php
                        $cr = $wagon->cargoRequest;
                        $ownerName = $wagon->owner?->name ?? $cr?->owner?->name;
                        $isLoadedUnbilled = $wagon->loaded_weight !== null && $wagon->tariff_id !== null;
                    @endphp
                    <tr>
                        <td>{{ ($wagons->currentPage() - 1) * $wagons->perPage() + $loop->iteration }}</td>
                        <td class="fw-bold">
                            @if($cr)
                                <a href="{{ route('cargo.show', $cr) }}" class="text-decoration-none">{{ $cr->request_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $cr?->request_date_shamsi ?? '—' }}</td>
                        <td class="fw-bold font-monospace">{{ $wagon->wagon_number }}</td>
                        <td>{{ $wagon->cargo_description ?: '—' }}</td>
                        <td>{{ $wagon->line_number ?: '—' }}</td>
                        <td>
                            @if($ownerName)
                                {{ $ownerName }}
                            @else
                                <span class="badge bg-warning text-dark">ناشناس</span>
                            @endif
                        </td>
                        <td>{{ $cr?->representative?->name ?? '—' }}</td>
                        <td class="small">{{ $wagon->tariff?->title ?? '—' }}</td>
                        <td>{{ $wagon->loaded_weight !== null ? $wagon->loaded_weight : '—' }}</td>
                        <td>
                            @if($isLoadedUnbilled)
                                <span class="badge bg-info text-dark">صورتحساب‌نشده (بارگیری‌شده)</span>
                            @else
                                <span class="badge bg-warning text-dark">در انتظار بارگیری</span>
                            @endif
                        </td>
                        <td class="no-print text-nowrap">
                            @if($cr)
                                @if($isLoadedUnbilled)
                                    <a href="{{ route('cargo.show', $cr) }}" class="btn btn-sm btn-success">
                                        صدور صورتحساب / مشاهده
                                    </a>
                                @else
                                    <a href="{{ route('cargo.show', $cr) }}" class="btn btn-sm btn-primary">
                                        ثبت بارگیری
                                    </a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            هیچ واگنی در انتظار بارگیری یا صورتحساب‌نشده یافت نشد.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 no-print">
        {{ $wagons->links() }}
    </div>
@endsection
