@extends('layouts.app')
@section('title', 'صدور صورتحساب')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">صدور صورتحساب جدید</h4>
            <div class="text-muted small">
                صاحبان کالای دارای واگن بارگیری‌شده در نوار زیر مشخص شده‌اند؛ با انتخاب هر صاحب کالا، نماینده و تمام واگن‌های آماده به‌طور خودکار انتخاب و محاسبه می‌شوند.
            </div>
        </div>
        <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary btn-sm">لیست صورتحساب‌ها</a>
    </div>

    @php
        $readyCounts = $readyCountsByOwner ?? collect();
        $ownersWithReady = $owners->filter(fn($o) => ($readyCounts[$o->id] ?? 0) > 0);
        $unknownReady = (int) ($unknownOwnerReadyCount ?? 0);
        $unassignedList = $unassignedWagons ?? collect();
    @endphp

    @if($unknownReady > 0)
        <div class="card border-warning bg-warning-subtle p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-bold text-dark">
                        {{ number_format($unknownReady) }} واگن بارگیری‌شده با صاحب کالای «ناشناس» آماده تعیین صاحب کالا و صدور صورتحساب است:
                    </div>
                    <div class="small text-dark mt-1">
                        هنگام صدور صورتحساب، مشخص کردن نام صاحب کالا الزامی است. با انتخاب صاحب کالای قطعی از کادر زیر، واگن‌های ناشناس نیز در جدول ظاهر می‌شوند تا با تیک زدن آن‌ها، هم‌زمان صاحب کالا ثبت و فاکتور صادر شود.
                    </div>
                </div>
                <div class="d-flex flex-column gap-2 w-100 mt-2">
                    @foreach($unassignedList->groupBy('cargo_request_id') as $crId => $wGroup)
                        @php
                            $firstW = $wGroup->first();
                            $repObj = $firstW?->cargoRequest?->representative;
                            $linkedOwners = ($repObj?->owners ?? collect())->where('is_active', true)->sortBy('name')->values();
                        @endphp
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 bg-white rounded border p-2">
                            <div class="small">
                                <strong>درخواست {{ $firstW?->cargoRequest?->request_number }}</strong>
                                ({{ $wGroup->count() }} واگن) —
                                <strong>نماینده: {{ $repObj?->name ?? '—' }}</strong>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span class="small text-muted me-1">انتخاب صاحب کالای مرتبط با این نماینده:</span>
                                @forelse($linkedOwners as $lo)
                                    <a href="{{ route('billing.create', ['owner_id' => $lo->id, 'cargo_request_id' => $crId, 'representative_id' => $repObj?->id]) }}"
                                       class="btn btn-sm btn-success">
                                        {{ $lo->name }}
                                    </a>
                                @empty
                                    <span class="badge bg-danger">هیچ صاحب کالایی به این نماینده تخصیص نیافته است</span>
                                @endforelse
                                <a href="{{ route('cargo.show', $crId) }}" class="btn btn-sm btn-outline-secondary">
                                    مشاهده درخواست
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($ownersWithReady->isNotEmpty())
        <div class="card border-success bg-light p-3 mb-3">
            <div class="small fw-bold text-success mb-2">
                صاحبان کالای دارای واگن آماده صدور صورتحساب (انتخاب سریع با ۱ کلیک):
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($ownersWithReady as $readyOwner)
                    <a href="{{ route('billing.create', ['owner_id' => $readyOwner->id]) }}"
                       class="btn btn-sm {{ (int) $ownerId === (int) $readyOwner->id ? 'btn-success' : 'btn-outline-success bg-white' }}">
                        {{ $readyOwner->name }}
                        <span class="badge {{ (int) $ownerId === (int) $readyOwner->id ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">
                            {{ $readyCounts[$readyOwner->id] }} واگن
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('billing.create') }}" class="card p-3 mb-4">
        @if(!empty($selectedCargoRequestId))
            <input type="hidden" name="cargo_request_id" value="{{ $selectedCargoRequestId }}">
        @endif
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="billing_owner_filter">
                    انتخاب صاحب کالا برای صدور صورتحساب <span class="text-danger">* (اجباری)</span>
                </label>
                <select name="owner_id" id="billing_owner_filter" class="form-select" onchange="this.form.submit()" required>
                    <option value="">انتخاب صاحب کالا (اجباری)…</option>
                    @foreach($owners as $owner)
                        @php($rc = $readyCounts[$owner->id] ?? 0)
                        <option value="{{ $owner->id }}" @selected((int) $ownerId === $owner->id)>
                            {{ $owner->name }}{{ $rc > 0 ? " ({$rc} واگن آماده)" : ($unknownReady > 0 ? " (امکان تخصیص {$unknownReady} واگن ناشناس)" : '') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if($ownerId)
        <form method="POST" action="{{ route('billing.store') }}" class="card p-4" id="billing-form" data-tax="{{ $taxPercentage }}">
            @csrf
            <input type="hidden" name="owner_id" value="{{ $ownerId }}">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="representative_id">نماینده (مرتبط با صاحب کالا) <span class="text-danger">*</span></label>
                    <select name="representative_id" id="representative_id" class="form-select @error('representative_id') is-invalid @enderror" required>
                        <option value="">انتخاب نماینده…</option>
                        @php($selectedRepId = old('representative_id', $defaultRepresentativeId ?? null))
                        @foreach($representatives as $rep)
                            <option value="{{ $rep->id }}" @selected((int) $selectedRepId === (int) $rep->id)>{{ $rep->name }}</option>
                        @endforeach
                    </select>
                    @error('representative_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="billing_date_shamsi">تاریخ صدور (شمسی) <span class="text-danger">*</span></label>
                    <input type="text" name="billing_date_shamsi" id="billing_date_shamsi"
                           value="{{ old('billing_date_shamsi', $billingDateShamsi) }}"
                           class="form-control jalali-datepicker" autocomplete="off" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tax_display">درصد مالیات بر ارزش افزوده</label>
                    <input type="text" id="tax_display" class="form-control" value="{{ $taxPercentage }}%" disabled>
                </div>
            </div>

            @if($wagons->isEmpty())
                <div class="alert alert-warning">
                    برای این صاحب کالا واگن بارگیری‌شده و تسویه‌نشده‌ای وجود ندارد.
                    (ابتدا در بخش درخواست بارگیری، وزن و تعرفه واگن‌ها را ثبت کنید.)
                </div>
            @else
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="small text-muted">
                        واگن‌های آماده به‌صورت پیش‌فرض انتخاب شده‌اند؛ در صورت نیاز می‌توانید تیک هر واگن را بردارید یا تعرفه/وزن را تغییر دهید.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered bg-white align-middle">
                        <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="select-all" aria-label="انتخاب همه واگن‌ها" checked></th>
                            <th>شماره واگن</th>
                            <th>درخواست</th>
                            <th>شرح کالا</th>
                            <th style="min-width: 240px;">تعرفه</th>
                            <th style="min-width: 120px;">وزن (تن)</th>
                            <th>هزینه پایه (ریال)</th>
                            <th>مالیات (ریال)</th>
                            <th>جمع کل (ریال)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $hasDirectOwnerWagons = $wagons->contains(fn($w) => (int) $w->effectiveOwnerId() === (int) $ownerId);
                        @endphp
                        @foreach($wagons as $i => $wagon)
                            @php
                                $isUnknownWagon = $wagon->effectiveOwnerId() === null;
                                if (!empty($selectedCargoRequestId)) {
                                    $shouldCheck = (int) $wagon->cargo_request_id === (int) $selectedCargoRequestId;
                                } elseif ($hasDirectOwnerWagons) {
                                    $shouldCheck = !$isUnknownWagon;
                                } else {
                                    $shouldCheck = true;
                                }
                            @endphp
                            <tr class="wagon-billing-row {{ $isUnknownWagon ? 'table-warning' : '' }}">
                                <td>
                                    <input type="checkbox" class="wagon-check" data-index="{{ $i }}"
                                           aria-label="انتخاب واگن {{ $wagon->wagon_number }}"
                                           @checked($shouldCheck)>
                                    <input type="hidden" name="wagons[{{ $i }}][id]" value="{{ $wagon->id }}" {{ $shouldCheck ? '' : 'disabled' }}>
                                </td>
                                <td class="fw-bold font-monospace">{{ $wagon->wagon_number }}</td>
                                <td>
                                    <div>{{ $wagon->cargoRequest?->request_number }}</div>
                                    <div class="small text-muted">نماینده: {{ $wagon->cargoRequest?->representative?->name ?? '—' }}</div>
                                    @if($isUnknownWagon)
                                        <span class="badge bg-warning text-dark mt-1">ثبت‌شده به‌صورت ناشناس</span>
                                    @endif
                                </td>
                                <td>{{ $wagon->cargo_description ?: '—' }}</td>
                                <td>
                                    <select name="wagons[{{ $i }}][tariff_id]" class="form-select form-select-sm tariff-select" {{ $shouldCheck ? '' : 'disabled' }}>
                                        @foreach($tariffs as $tariff)
                                            <option value="{{ $tariff->id }}"
                                                    data-rate="{{ $tariff->rate }}"
                                                    data-capacity="{{ $tariff->capacity }}"
                                                @selected($wagon->tariff_id === $tariff->id)>
                                                {{ $tariff->title }} ({{ $tariff->capacity }} تن — {{ number_format($tariff->rate) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" max="200"
                                           name="wagons[{{ $i }}][weight]"
                                           value="{{ $wagon->loaded_weight }}"
                                           class="form-control form-control-sm weight-input" {{ $shouldCheck ? '' : 'disabled' }}>
                                </td>
                                <td class="cell-base">0</td>
                                <td class="cell-tax">0</td>
                                <td class="cell-total fw-bold">0</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">جمع کل صورتحساب:</td>
                            <td id="sum-base">0</td>
                            <td id="sum-tax">0</td>
                            <td id="sum-total" class="text-success fs-6">0</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="billing_notes">توضیحات</label>
                    <textarea name="notes" id="billing_notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <button class="btn btn-success fw-semibold px-4" type="submit">صدور قطعی صورتحساب</button>
            @endif
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('billing-form');
            if (!form) return;

            const taxPct = parseFloat(form.dataset.tax || '0');
            const fmt = n => Math.round(n).toLocaleString('en-US');

            function recalc() {
                let sumBase = 0, sumTax = 0;

                form.querySelectorAll('.wagon-billing-row').forEach(row => {
                    const checked = row.querySelector('.wagon-check').checked;
                    const select = row.querySelector('.tariff-select');
                    const weightInput = row.querySelector('.weight-input');
                    const hiddenId = row.querySelector('input[type="hidden"]');

                    [select, weightInput, hiddenId].forEach(el => el.disabled = !checked);

                    if (!checked) {
                        row.querySelector('.cell-base').textContent = '0';
                        row.querySelector('.cell-tax').textContent = '0';
                        row.querySelector('.cell-total').textContent = '0';
                        return;
                    }

                    const opt = select.options[select.selectedIndex];
                    const rate = parseFloat(opt.dataset.rate || '0');
                    const cap = parseFloat(opt.dataset.capacity || '1');
                    const w = parseFloat(weightInput.value || '0');

                    const base = cap > 0 ? Math.round((w / cap) * rate) : 0;
                    const tax = Math.round(base * (taxPct / 100));

                    row.querySelector('.cell-base').textContent = fmt(base);
                    row.querySelector('.cell-tax').textContent = fmt(tax);
                    row.querySelector('.cell-total').textContent = fmt(base + tax);

                    sumBase += base;
                    sumTax += tax;
                });

                document.getElementById('sum-base').textContent = fmt(sumBase);
                document.getElementById('sum-tax').textContent = fmt(sumTax);
                document.getElementById('sum-total').textContent = fmt(sumBase + sumTax);
            }

            form.addEventListener('change', recalc);
            form.addEventListener('input', recalc);

            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    form.querySelectorAll('.wagon-check').forEach(cb => cb.checked = selectAll.checked);
                    recalc();
                });
            }

            // محاسبه اولیه به محض بارگذاری صفحه برای واگن‌های از پیش انتخاب‌شده
            recalc();
        })();
    </script>
@endpush
