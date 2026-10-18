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
    @endphp

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
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="billing_owner_filter">انتخاب صاحب کالا برای بارگذاری واگن‌های تسویه‌نشده</label>
                <select name="owner_id" id="billing_owner_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">انتخاب کنید…</option>
                    @foreach($owners as $owner)
                        @php($rc = $readyCounts[$owner->id] ?? 0)
                        <option value="{{ $owner->id }}" @selected((int) $ownerId === $owner->id)>
                            {{ $owner->name }}{{ $rc > 0 ? " ({$rc} واگن آماده)" : '' }}
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
                    <label class="form-label" for="representative_id">نماینده (انتخاب خودکار از درخواست بارگیری)</label>
                    <select name="representative_id" id="representative_id" class="form-select">
                        <option value="">—</option>
                        @php($selectedRepId = old('representative_id', $defaultRepresentativeId ?? null))
                        @foreach($representatives as $rep)
                            <option value="{{ $rep->id }}" @selected((int) $selectedRepId === (int) $rep->id)>{{ $rep->name }}</option>
                        @endforeach
                    </select>
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
                        @foreach($wagons as $i => $wagon)
                            @php
                                $shouldCheck = empty($selectedCargoRequestId) || (int) $wagon->cargo_request_id === (int) $selectedCargoRequestId;
                            @endphp
                            <tr class="wagon-billing-row">
                                <td>
                                    <input type="checkbox" class="wagon-check" data-index="{{ $i }}"
                                           aria-label="انتخاب واگن {{ $wagon->wagon_number }}"
                                           @checked($shouldCheck)>
                                    <input type="hidden" name="wagons[{{ $i }}][id]" value="{{ $wagon->id }}" {{ $shouldCheck ? '' : 'disabled' }}>
                                </td>
                                <td class="fw-bold font-monospace">{{ $wagon->wagon_number }}</td>
                                <td>{{ $wagon->cargoRequest?->request_number }}</td>
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
