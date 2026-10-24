@extends('layouts.app')
@section('title', 'کارتابل عملیات بارگیری')

@section('content')
    @php
        $qs = $queueStats ?? [
            'pending_loading' => 0,
            'pending_loading_wagons' => 0,
            'ready_to_bill' => 0,
            'my_today_requests' => 0,
            'my_today_billings' => 0,
            'total_count' => 0,
        ];
        $activeStatus = request('status');
        $activeQueue = $queueFilter ?? request('queue');
        $pendingWagonsList = $pendingLoadingWagons ?? collect();
        $pendingReportUrl = \Illuminate\Support\Facades\Route::has('reports.pending-loading')
            ? route('reports.pending-loading')
            : url('/reports/pending-loading');
        $pendingExcelUrl = \Illuminate\Support\Facades\Route::has('reports.pending-loading')
            ? route('reports.pending-loading', ['export' => 'excel'])
            : url('/reports/pending-loading?export=excel');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">کارتابل عملیات بارگیری</h4>
            <div class="text-muted small">مدیریت سریع درخواست‌ها، ثبت بارگیری، گزارش واگن‌های در انتظار بارگیری و صدور یک‌کلیکی صورتحساب</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="#pending-loading-report-card" class="btn btn-warning fw-semibold">
                گزارش در انتظار بارگیری ({{ number_format($pendingWagonsList->count()) }} واگن)
            </a>
            <a href="{{ $pendingReportUrl }}" class="btn btn-outline-dark">
                صفحه گزارش در انتظار بارگیری
            </a>
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
                                        <div class="d-inline-flex flex-column align-items-start gap-1">
                                            <form method="POST" action="{{ route('cargo.quick-bill', $req) }}" class="d-inline-flex gap-1 align-items-center bg-warning-subtle border border-warning rounded p-1">
                                                @csrf
                                                <select name="owner_id" class="form-select form-select-sm py-0" style="min-width: 175px; height: 28px; font-size: 0.78rem;" required>
                                                    <option value="">صاحب کالا (مرتبط با نماینده)…</option>
                                                    @foreach($reqRepOwners as $owner)
                                                        <option value="{{ $owner->id }}" @selected($reqRepOwners->count() === 1)>{{ $owner->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success py-0 px-2" style="height: 28px; font-size: 0.78rem;" title="تعیین صاحب کالا و صدور صورتحساب">
                                                    تعیین صاحب کالا و صدور صورتحساب
                                                </button>
                                            </form>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 open-cargo-quick-owner-btn"
                                                    style="font-size: 0.75rem;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cargoIndexQuickOwnerModal"
                                                    data-cargo-id="{{ $req->id }}"
                                                    data-request-number="{{ $req->request_number }}"
                                                    data-rep-id="{{ $req->representative_id }}"
                                                    data-rep-name="{{ $req->representative?->name }}">
                                                + تعریف صاحب کالای جدید
                                            </button>
                                        </div>
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

    <div class="mt-3 mb-4">{{ $cargoRequests->links() }}</div>

    {{-- گزارش واگن‌های در انتظار بارگیری و صورتحساب‌نشده در کارتابل عملیات بارگیری --}}
    @php
        $pendingOnlyCount = $pendingWagonsList->filter(fn($w) => $w->loaded_weight === null || $w->tariff_id === null)->count();
        $unbilledLoadedCount = $pendingWagonsList->filter(fn($w) => $w->loaded_weight !== null && $w->tariff_id !== null)->count();
    @endphp
    <div class="card border-warning mb-4" id="pending-loading-report-card">
        <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    گزارش واگن‌های در انتظار بارگیری و صورتحساب‌نشده
                    <span class="badge bg-dark ms-1">{{ number_format($pendingWagonsList->count()) }} واگن کل</span>
                    <span class="badge bg-warning text-dark ms-1">{{ number_format($pendingOnlyCount) }} در انتظار بارگیری</span>
                    <span class="badge bg-info text-dark ms-1">{{ number_format($unbilledLoadedCount) }} صورتحساب‌نشده</span>
                </h5>
                <div class="small text-muted">
                    شامل کلیه واگن‌های در انتظار ثبت بارگیری و واگن‌های بارگیری‌شده‌ای که هنوز صورتحساب برای آن‌ها صادر نشده است.
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $pendingExcelUrl }}" class="btn btn-sm btn-success fw-semibold">
                    دریافت خروجی اکسل (Excel)
                </a>
                <a href="{{ $pendingReportUrl }}" class="btn btn-sm btn-outline-dark">
                    مشاهده و فیلتر در صفحه گزارش
                </a>
            </div>
        </div>
        <div class="card-body p-0">
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
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($pendingWagonsList as $pw)
                        @php
                            $pwCr = $pw->cargoRequest;
                            $pwOwnerName = $pw->owner?->name ?? $pwCr?->owner?->name;
                            $pwLoadedUnbilled = $pw->loaded_weight !== null && $pw->tariff_id !== null;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">
                                @if($pwCr)
                                    <a href="{{ route('cargo.show', $pwCr) }}" class="text-decoration-none">{{ $pwCr->request_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $pwCr?->request_date_shamsi ?? '—' }}</td>
                            <td class="fw-bold font-monospace">{{ $pw->wagon_number }}</td>
                            <td>{{ $pw->cargo_description ?: '—' }}</td>
                            <td>{{ $pw->line_number ?: '—' }}</td>
                            <td>
                                @if($pwOwnerName)
                                    {{ $pwOwnerName }}
                                @else
                                    <span class="badge bg-warning text-dark">ناشناس</span>
                                @endif
                            </td>
                            <td>{{ $pwCr?->representative?->name ?? '—' }}</td>
                            <td class="small">{{ $pw->tariff?->title ?? '—' }}</td>
                            <td>{{ $pw->loaded_weight !== null ? $pw->loaded_weight : '—' }}</td>
                            <td>
                                @if($pwLoadedUnbilled)
                                    <span class="badge bg-info text-dark">صورتحساب‌نشده (بارگیری‌شده)</span>
                                @else
                                    <span class="badge bg-warning text-dark">در انتظار بارگیری</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($pwCr)
                                    @if($pwLoadedUnbilled)
                                        <a href="{{ route('cargo.show', $pwCr) }}" class="btn btn-sm btn-success">
                                            صدور صورتحساب / مشاهده
                                        </a>
                                    @else
                                        <a href="{{ route('cargo.show', $pwCr) }}" class="btn btn-sm btn-primary">
                                            ثبت بارگیری
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                هم‌اکنون هیچ واگنی در انتظار بارگیری یا صورتحساب‌نشده وجود ندارد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- مودال تعریف صاحب کالای جدید زیر تعیین صاحب کالا و صدور صورتحساب --}}
    @php $modalReps = $representatives ?? collect(); @endphp
    <div class="modal fade" id="cargoIndexQuickOwnerModal" tabindex="-1" aria-labelledby="cargoIndexQuickOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('owners.store') }}" class="modal-content">
                @csrf
                <input type="hidden" name="redirect_to" value="cargo.index">
                <input type="hidden" name="cargo_request_id" id="modal_cargo_req_id" value="">
                <input type="hidden" name="assign_to_cargo_request" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="cargoIndexQuickOwnerModalLabel">تعریف صاحب کالای جدید</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3" id="modal_cargo_req_hint" style="display: none;"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="idx_quick_owner_name">نام صاحب کالا <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="idx_quick_owner_name" class="form-control" required placeholder="نام شخص حقیقی یا شرکت">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="idx_quick_owner_national_id">کد ملی / شناسه ملی</label>
                            <input type="text" name="national_id" id="idx_quick_owner_national_id" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="idx_quick_owner_phone">تلفن</label>
                            <input type="text" name="phone" id="idx_quick_owner_phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="idx_quick_owner_economic_code">کد اقتصادی</label>
                            <input type="text" name="economic_code" id="idx_quick_owner_economic_code" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="idx_quick_owner_address">آدرس</label>
                            <input type="text" name="address" id="idx_quick_owner_address" class="form-control">
                        </div>
                        @if($modalReps->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label mb-1">نماینده مرتبط با این صاحب کالا</label>
                                <div class="row g-2 border rounded p-2 bg-light" style="max-height: 170px; overflow-y: auto;">
                                    @foreach($modalReps as $rep)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input idx-quick-owner-rep-check"
                                                       name="representative_ids[]"
                                                       value="{{ $rep->id }}"
                                                       id="idx_rep_{{ $rep->id }}">
                                                <label class="form-check-label small" for="idx_rep_{{ $rep->id }}">{{ $rep->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="{{ route('owners.create', ['redirect_to' => 'cargo.index']) }}" id="idx_full_owner_create_link" class="btn btn-link btn-sm text-decoration-none">
                        باز کردن فرم کامل تعریف صاحب کالا
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary fw-semibold">ذخیره و تخصیص به درخواست</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('cargoIndexQuickOwnerModal');
            if (!modalEl) return;

            document.querySelectorAll('.open-cargo-quick-owner-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cargoId = btn.getAttribute('data-cargo-id') || '';
                    const reqNum = btn.getAttribute('data-request-number') || '';
                    const repId = btn.getAttribute('data-rep-id') || '';
                    const repName = btn.getAttribute('data-rep-name') || '';

                    const cargoInput = document.getElementById('modal_cargo_req_id');
                    if (cargoInput) cargoInput.value = cargoId;

                    const hint = document.getElementById('modal_cargo_req_hint');
                    if (hint) {
                        if (reqNum) {
                            hint.textContent = 'صاحب کالای جدید پس از ثبت، به نماینده «' + (repName || '—') + '» متصل شده و روی درخواست «' + reqNum + '» قرار می‌گیرد.';
                            hint.style.display = 'block';
                        } else {
                            hint.style.display = 'none';
                        }
                    }

                    modalEl.querySelectorAll('.idx-quick-owner-rep-check').forEach(function (chk) {
                        chk.checked = (repId !== '' && String(chk.value) === String(repId));
                    });
                });
            });

            modalEl.addEventListener('shown.bs.modal', function () {
                const nameInput = document.getElementById('idx_quick_owner_name');
                if (nameInput) nameInput.focus();
            });
        });
    </script>
@endpush
