@extends('layouts.app')
@section('title', 'کارتابل عملیات بارگیری')

@section('content')
    @php
        $qs = $queueStats ?? [
            'pending_loading' => 0,
            'ready_to_bill' => 0,
            'my_today_requests' => 0,
            'my_today_billings' => 0,
            'total_count' => 0,
        ];
        $activeStatus = request('status');
        $activeQueue = $queueFilter ?? request('queue');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">کارتابل عملیات بارگیری</h4>
            <div class="text-muted small">مدیریت سریع درخواست‌ها، ثبت بارگیری و صدور یک‌کلیکی صورتحساب</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('billing.create') }}" class="btn btn-outline-success">صدور صورتحساب</a>
            <a href="{{ route('cargo.create') }}" class="btn btn-primary fw-semibold">+ ثبت درخواست جدید</a>
        </div>
    </div>

    {{-- کارتابل عملیاتی روزانه اپراتور (فیلتر با ۱ کلیک) --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('cargo.index', ['status' => 'pending']) }}"
               class="card p-3 text-decoration-none h-100 {{ $activeStatus === 'pending' ? 'border-warning bg-warning-subtle' : 'border-warning' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">در انتظار ثبت بارگیری</div>
                        <div class="small text-muted">نیازمند ثبت تعرفه و وزن</div>
                    </div>
                    <span class="badge bg-warning text-dark fs-5">{{ number_format($qs['pending_loading']) }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('cargo.index', ['status' => 'loaded']) }}"
               class="card p-3 text-decoration-none h-100 {{ $activeStatus === 'loaded' ? 'border-success bg-success-subtle' : 'border-success' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">آماده صدور صورتحساب</div>
                        <div class="small text-muted">بارگیری‌شده و تسویه‌نشده</div>
                    </div>
                    <span class="badge bg-success fs-5">{{ number_format($qs['ready_to_bill']) }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('cargo.index', ['queue' => 'my_today']) }}"
               class="card p-3 text-decoration-none h-100 {{ $activeQueue === 'my_today' ? 'border-primary bg-primary-subtle' : 'border-primary' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">ثبت‌شده‌های امروز من</div>
                        <div class="small text-muted">{{ number_format($qs['my_today_billings']) }} فاکتور صادرشده امروز</div>
                    </div>
                    <span class="badge bg-primary fs-5">{{ number_format($qs['my_today_requests']) }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('cargo.index') }}"
               class="card p-3 text-decoration-none h-100 {{ !$activeStatus && !$activeQueue ? 'border-secondary bg-light' : '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark">همه درخواست‌ها</div>
                        <div class="small text-muted">نمایش کل پرونده‌ها</div>
                    </div>
                    <span class="badge bg-secondary fs-5">{{ number_format($qs['total_count']) }}</span>
                </div>
            </a>
        </div>
    </div>

    <form method="GET" class="card p-3 mb-3">
        @if($activeQueue)
            <input type="hidden" name="queue" value="{{ $activeQueue }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="cargo_search">جستجو (شماره درخواست، شماره واگن، شرح کالا، صاحب کالا)</label>
                <input type="text" name="search" id="cargo_search" value="{{ request('search') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cargo_owner_id">صاحب کالا</label>
                <select name="owner_id" id="cargo_owner_id" class="form-select">
                    <option value="">همه</option>
                    <option value="unknown" @selected(request('owner_id') === 'unknown')>— ناشناس (بدون صاحب کالا) —</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected(request('owner_id') !== 'unknown' && (int) request('owner_id') === $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cargo_status">وضعیت</label>
                <select name="status" id="cargo_status" class="form-select">
                    <option value="">همه</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-secondary flex-grow-1" type="submit">فیلتر</button>
                @if(request()->hasAny(['search', 'owner_id', 'status', 'queue']))
                    <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary" title="پاک کردن فیلترها">×</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>شماره درخواست</th>
                    <th>صاحب کالا</th>
                    <th>نماینده</th>
                    <th>شرح کالا / واگن‌ها</th>
                    <th>تاریخ درخواست</th>
                    <th>تعداد واگن</th>
                    <th>وضعیت</th>
                    <th>عملیات سریع</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cargoRequests as $req)
                    @php
                        $distinctOwners = $req->wagons->map(fn($w) => $w->ownerName())->unique()->filter()->values();
                        $hasOwnerTransfer = $req->wagons->contains(fn($w) => $w->isOwnerChanged());
                        $hasNumberEdit = $req->wagons->contains(fn($w) => $w->isNumberEdited());
                        $distinctDescs = $req->wagons->pluck('cargo_description')->filter()->unique()->values();
                        $readyWagonsCount = $req->wagons->filter(fn($w) => !$w->is_settled && $w->isLoaded() && $w->tariff_id)->count();
                    @endphp
                    <tr>
                        <td class="fw-bold">
                            <a href="{{ route('cargo.show', $req) }}" class="text-decoration-none">{{ $req->request_number }}</a>
                        </td>
                        <td>
                            @if($req->owner)
                                <div>{{ $req->owner->name }}</div>
                            @else
                                <span class="badge bg-warning text-dark">ناشناس (تعیین هنگام صدور فاکتور)</span>
                            @endif
                            @if($hasOwnerTransfer)
                                <span class="badge bg-warning text-dark mt-1">دارای ویرایش صاحب کالا</span>
                                <div class="small text-muted">{{ $distinctOwners->implode('، ') }}</div>
                            @endif
                            @if($hasNumberEdit)
                                <span class="badge bg-info text-dark mt-1">دارای اصلاح شماره واگن</span>
                            @endif
                        </td>
                        <td>{{ $req->representative?->name ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $distinctDescs->isNotEmpty() ? $distinctDescs->implode('، ') : '—' }}</div>
                            <div class="small text-muted font-monospace">
                                {{ $req->wagons->take(4)->pluck('wagon_number')->implode('، ') }}{{ $req->wagons->count() > 4 ? ' و …' : '' }}
                            </div>
                        </td>
                        <td>{{ $req->request_date_shamsi }}</td>
                        <td>{{ $req->wagons->count() }}</td>
                        <td>
                            @php $badge = ['pending' => 'warning text-dark', 'loaded' => 'info text-dark', 'billed' => 'success', 'cancelled' => 'danger'][$req->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $badge }}">{{ $req->statusLabel() }}</span>
                        </td>
                        <td class="text-nowrap">
                            <div class="d-inline-flex gap-1 align-items-center">
                                @if($req->status === 'pending')
                                    <a href="{{ route('cargo.show', $req) }}" class="btn btn-sm btn-primary">
                                        ثبت بارگیری
                                    </a>
                                @else
                                    <a href="{{ route('cargo.show', $req) }}" class="btn btn-sm btn-outline-primary">
                                        مشاهده
                                    </a>
                                @endif

                                @if($req->allWagonsFullySettled())
                                    <a href="{{ \Illuminate\Support\Facades\Route::has('cargo.print') ? route('cargo.print', $req) : url('/cargo/'.$req->id.'/print') }}"
                                       target="_blank" rel="noopener"
                                       class="btn btn-sm btn-success fw-semibold">
                                        چاپ برگه ۴ بخشی
                                    </a>
                                @endif

                                @if($readyWagonsCount > 0)
                                    @if($req->owner_id)
                                        <form method="POST" action="{{ route('cargo.quick-bill', $req) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="صدور مستقیم صورتحساب برای {{ $readyWagonsCount }} واگن بارگیری‌شده">
                                                صدور صورتحساب
                                            </button>
                                        </form>
                                    @else
                                        @php $reqRepOwners = ($req->representative?->owners ?? collect())->where('is_active', true)->sortBy('name')->values(); @endphp
                                        <form method="POST" action="{{ route('cargo.quick-bill', $req) }}" class="d-inline-flex gap-1 align-items-center bg-warning-subtle border border-warning rounded p-1">
                                            @csrf
                                            <select name="owner_id" class="form-select form-select-sm py-0" style="min-width: 175px; height: 28px; font-size: 0.78rem;" required>
                                                <option value="">صاحب کالا (مرتبط با نماینده)…</option>
                                                @foreach($reqRepOwners as $owner)
                                                    <option value="{{ $owner->id }}" @selected($reqRepOwners->count() === 1)>{{ $owner->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="height: 28px; font-size: 0.78rem;" title="تعیین صاحب کالا و صدور صورتحساب">
                                                تعیین صاحب کالا + صدور فاکتور
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">موردی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $cargoRequests->links() }}</div>
@endsection
