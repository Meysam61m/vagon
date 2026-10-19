@extends('layouts.app')
@section('title', 'جزئیات درخواست بارگیری')

@section('content')
    @php
        $readyToBillCount = $cargo->wagons->filter(fn($w) => !$w->is_settled && $w->isLoaded() && $w->tariff_id)->count();
        $unsettledCount = $cargo->wagons->where('is_settled', false)->count();
        $firstSuggestedTariffId = collect($suggestedTariffs ?? [])->filter()->first()
            ?: $cargo->wagons->whereNotNull('tariff_id')->first()?->tariff_id;
        $repOwners = ($cargo->representative?->owners ?? collect())->where('is_active', true)->sortBy('name')->values();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0">درخواست بارگیری: {{ $cargo->request_number }}</h4>
            <span class="badge bg-secondary fs-6">{{ $cargo->statusLabel() }}</span>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if($readyToBillCount > 0)
                @if($cargo->owner_id)
                    <form method="POST" action="{{ route('cargo.quick-bill', ['cargo' => $cargo, 'print' => 1]) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">
                            صدور و چاپ فوری صورتحساب ({{ $readyToBillCount }} واگن)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('cargo.quick-bill', $cargo) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            صدور صورتحساب
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('cargo.quick-bill', $cargo) }}" class="d-inline-flex gap-1 align-items-center bg-warning-subtle border border-warning rounded p-1">
                        @csrf
                        <select name="owner_id" class="form-select form-select-sm" style="min-width: 230px;" required>
                            <option value="">صاحب کالا (مرتبط با {{ $cargo->representative?->name }})…</option>
                            @foreach($repOwners as $owner)
                                <option value="{{ $owner->id }}" @selected($repOwners->count() === 1)>{{ $owner->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="print" value="1" class="btn btn-success btn-sm fw-semibold text-nowrap">
                            تعیین صاحب کالا + چاپ فاکتور
                        </button>
                        <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
                            صدور فاکتور
                        </button>
                    </form>
                @endif
            @endif
            <a href="{{ route('cargo.create') }}" class="btn btn-outline-primary btn-sm">+ درخواست جدید</a>
            <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary btn-sm">لیست درخواست‌ها</a>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <strong>صاحب کالا:</strong>
                @if($cargo->owner)
                    {{ $cargo->owner->name }}
                @else
                    <span class="badge bg-warning text-dark">ناشناس (تعیین هنگام صدور صورتحساب)</span>
                @endif
            </div>
            <div class="col-md-3"><strong>نماینده:</strong> {{ $cargo->representative?->name ?? '—' }}</div>
            <div class="col-md-3"><strong>تاریخ درخواست:</strong> {{ $cargo->request_date_shamsi }}</div>
            <div class="col-md-3"><strong>اپراتور ثبت‌کننده:</strong> {{ $cargo->operator?->name ?? '—' }}</div>
        </div>
        @if($cargo->notes)
            <div class="mt-3"><strong>توضیحات:</strong> {{ $cargo->notes }}</div>
        @endif
    </div>

    @if($cargo->status !== 'billed')
        <form method="POST" action="{{ route('cargo.load', $cargo) }}" class="card p-4 mb-4" id="cargo-loading-form">
            @csrf
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-5">
                    <h6 class="mb-1">ثبت عملیات بارگیری ({{ $cargo->wagons->count() }} واگن)</h6>
                    <div class="text-muted small">
                        ثبت بارگیری با صاحب کالای «ناشناس» مجاز است؛ اما <strong>هنگام صدور صورتحساب</strong> انتخاب نام صاحب کالای مرتبط با نماینده الزامی است.
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1" for="loading_owner_id">
                        صاحب کالا (مرتبط با نماینده «{{ $cargo->representative?->name }}»)
                        @if(!$cargo->owner_id)
                            <span class="badge bg-warning text-dark small">فعلاً ناشناس — اجباری برای فاکتور</span>
                        @endif
                    </label>
                    <select name="owner_id" id="loading_owner_id" class="form-select @error('owner_id') is-invalid @enderror">
                        <option value="">— ناشناس / نامشخص (فقط ذخیره بارگیری) —</option>
                        @foreach($repOwners as $owner)
                            <option value="{{ $owner->id }}" @selected((int) old('owner_id', $cargo->owner_id) === (int) $owner->id)>
                                {{ $owner->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('owner_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($repOwners->isEmpty())
                        <div class="form-text text-danger small">
                            به نماینده «{{ $cargo->representative?->name }}» هنوز صاحب کالایی تخصیص داده نشده است.
                        </div>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1" for="loading_date_shamsi">تاریخ بارگیری (شمسی)</label>
                    <input type="text" name="loading_date_shamsi" id="loading_date_shamsi"
                           value="{{ old('loading_date_shamsi', $loadingDateShamsi) }}"
                           class="form-control jalali-datepicker" autocomplete="off" required>
                </div>
            </div>

            @if($unsettledCount > 0)
                {{-- نوار ابزار اعمال یکجای تعرفه و وزن کامل قرارداد برای حذف کار تکراری اپراتور --}}
                <div class="card bg-light border-primary p-3 mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1" for="bulk_tariff_select">
                                تعرفه یکجا برای همه واگن‌های این درخواست
                            </label>
                            <select id="bulk_tariff_select" class="form-select form-select-sm">
                                <option value="">انتخاب تعرفه برای اعمال گروهی…</option>
                                @foreach($tariffs as $tariff)
                                    @if($tariff->is_active)
                                        <option value="{{ $tariff->id }}"
                                                data-capacity="{{ $tariff->capacity }}"
                                                data-rate="{{ $tariff->rate }}"
                                                @selected((int) $firstSuggestedTariffId === (int) $tariff->id)>
                                            {{ $tariff->title }} — ظرفیت: {{ $tariff->capacity }} تن ({{ number_format($tariff->rate) }} ریال)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1" for="bulk_weight_input">
                                وزن هر واگن (تن — طبق قرارداد)
                            </label>
                            <input type="number" step="0.01" min="0.01" max="200" id="bulk_weight_input"
                                   class="form-control form-control-sm"
                                   placeholder="مثلاً 65">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="button" id="apply-bulk-loading-btn" class="btn btn-sm btn-primary flex-grow-1">
                                اعمال تعرفه و وزن به همه واگن‌ها
                            </button>
                            <button type="button" id="fill-all-capacity-btn" class="btn btn-sm btn-outline-dark" title="پر کردن وزن همه واگن‌ها بر اساس ظرفیت تعرفه انتخابی هر ردیف">
                                تکمیل ظرفیت همه
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>شماره واگن</th>
                        <th style="min-width: 190px;">شرح کالا <span class="text-danger">*</span></th>
                        <th>صاحب کالا</th>
                        <th style="min-width: 260px;">تعرفه</th>
                        <th style="min-width: 155px;">وزن بارگیری (تن)</th>
                        <th>وضعیت</th>
                        <th>صورتحساب</th>
                        <th>ویرایش واگن</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cargo->wagons as $wagon)
                        @php
                            $suggestedId = $suggestedTariffs[$wagon->id] ?? null;
                            $oldTariff = old('loading.'.$wagon->id.'.tariff_id', $wagon->tariff_id ?? $suggestedId);
                            $selectedTariffObj = $tariffs->firstWhere('id', (int) $oldTariff);
                            $defaultWeight = $wagon->loaded_weight ?? ($selectedTariffObj ? $selectedTariffObj->capacity : null);
                            $oldWeight = old('loading.'.$wagon->id.'.weight', $defaultWeight);
                            $isAutoSuggested = !$wagon->tariff_id && $suggestedId && (int) $oldTariff === (int) $suggestedId;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold font-monospace">{{ $wagon->wagon_number }}</div>
                                @if($wagon->isNumberEdited())
                                    <span class="badge bg-info text-dark mt-1">شماره اصلاح‌شده</span>
                                    <div class="small text-muted">قبلی: <span class="text-decoration-line-through">{{ $wagon->previous_wagon_number }}</span></div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->cargo_description ?: '—' }}
                                @else
                                    <input type="text"
                                           name="loading[{{ $wagon->id }}][description]"
                                           value="{{ old('loading.'.$wagon->id.'.description', $wagon->cargo_description) }}"
                                           class="form-control form-control-sm @error('loading.'.$wagon->id.'.description') is-invalid @enderror"
                                           data-loading-description
                                           placeholder="مثلاً میلگرد، کاشی">
                                    @error('loading.'.$wagon->id.'.description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </td>
                            <td>
                                <div>{{ $wagon->ownerName() }}</div>
                                @if($wagon->isOwnerChanged())
                                    <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                                    <div class="small text-muted">قبلی: {{ $wagon->previousOwner?->name ?? '—' }}</div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->tariff?->title ?? '—' }}
                                @else
                                    <select name="loading[{{ $wagon->id }}][tariff_id]"
                                            class="form-select form-select-sm wagon-tariff-select"
                                            data-wagon-id="{{ $wagon->id }}">
                                        <option value="">انتخاب تعرفه…</option>
                                        @foreach($tariffs as $tariff)
                                            <option value="{{ $tariff->id }}"
                                                    data-capacity="{{ $tariff->capacity }}"
                                                    @selected((string) $oldTariff === (string) $tariff->id)>
                                                {{ $tariff->title }}{{ $tariff->is_active ? '' : ' (غیرفعال)' }}
                                                — {{ $tariff->capacity }} تن
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($isAutoSuggested)
                                        <div class="small text-primary mt-1" data-suggested-hint>
                                            ✓ پیشنهاد خودکار از روی شرح کالا
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->loaded_weight }}
                                @else
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0.01" max="200"
                                               name="loading[{{ $wagon->id }}][weight]"
                                               value="{{ $oldWeight }}"
                                               class="form-control wagon-weight-input @error('loading.'.$wagon->id.'.weight') is-invalid @enderror"
                                               placeholder="مثلاً 65">
                                        <button type="button" class="btn btn-outline-secondary fill-row-capacity-btn" title="قرار دادن ظرفیت کامل تعرفه">
                                            کامل
                                        </button>
                                    </div>
                                    @error('loading.'.$wagon->id.'.weight')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    <span class="badge bg-success">تسویه شده</span>
                                @elseif($wagon->isLoaded())
                                    <span class="badge bg-info text-dark">بارگیری شده</span>
                                @else
                                    <span class="badge bg-warning text-dark">در انتظار بارگیری</span>
                                @endif
                            </td>
                            <td>
                                @if($wagon->billingItem?->billing)
                                    <a href="{{ route('billing.show', $wagon->billingItem->billing) }}">
                                        {{ $wagon->billingItem->billing->billing_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ \Illuminate\Support\Facades\Route::has('wagons.edit-owner') ? route('wagons.edit-owner', $wagon) : url('/wagons/'.$wagon->id.'/edit-owner') }}" class="btn btn-sm btn-outline-primary">
                                    ویرایش واگن
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button class="btn btn-success fw-semibold" type="submit" name="issue_billing" value="print">
                    ذخیره بارگیری و چاپ فوری صورتحساب (۱ کلیک)
                </button>
                <button class="btn btn-outline-success fw-semibold" type="submit" name="issue_billing" value="1">
                    ذخیره بارگیری و صدور صورتحساب
                </button>
                <button class="btn btn-primary" type="submit" name="issue_billing" value="0">
                    فقط ذخیره بارگیری
                </button>
            </div>
        </form>
    @else
        <div class="card p-4 mb-4">
            <h6 class="mb-3">واگن‌های این درخواست (صورتحساب‌شده)</h6>
            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>شماره واگن</th>
                        <th>شرح کالا</th>
                        <th>صاحب کالا</th>
                        <th>تعرفه</th>
                        <th>وزن (تن)</th>
                        <th>وضعیت</th>
                        <th>صورتحساب</th>
                        <th>ویرایش واگن</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cargo->wagons as $wagon)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold font-monospace">{{ $wagon->wagon_number }}</div>
                                @if($wagon->isNumberEdited())
                                    <span class="badge bg-info text-dark mt-1">شماره اصلاح‌شده</span>
                                    <div class="small text-muted">قبلی: <span class="text-decoration-line-through">{{ $wagon->previous_wagon_number }}</span></div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>{{ $wagon->cargo_description ?: '—' }}</td>
                            <td>
                                <div>{{ $wagon->ownerName() }}</div>
                                @if($wagon->isOwnerChanged())
                                    <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                                    <div class="small text-muted">قبلی: {{ $wagon->previousOwner?->name ?? '—' }}</div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>{{ $wagon->tariff?->title ?? '—' }}</td>
                            <td>{{ $wagon->loaded_weight ?? '—' }}</td>
                            <td><span class="badge bg-success">تسویه شده</span></td>
                            <td>
                                @if($wagon->billingItem?->billing)
                                    <a href="{{ route('billing.show', $wagon->billingItem->billing) }}" class="fw-bold">
                                        {{ $wagon->billingItem->billing->billing_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ \Illuminate\Support\Facades\Route::has('wagons.edit-owner') ? route('wagons.edit-owner', $wagon) : url('/wagons/'.$wagon->id.'/edit-owner') }}" class="btn btn-sm btn-outline-primary">
                                    ویرایش واگن
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <a href="{{ route('cargo.index') }}" class="btn btn-secondary">بازگشت</a>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bulkTariffSelect = document.getElementById('bulk_tariff_select');
            const bulkWeightInput = document.getElementById('bulk_weight_input');
            const applyBulkBtn = document.getElementById('apply-bulk-loading-btn');
            const fillAllCapacityBtn = document.getElementById('fill-all-capacity-btn');

            function syncBulkCapacityFromSelect() {
                if (!bulkTariffSelect || !bulkWeightInput) return;
                const opt = bulkTariffSelect.options[bulkTariffSelect.selectedIndex];
                if (opt && opt.dataset.capacity) {
                    bulkWeightInput.value = opt.dataset.capacity;
                }
            }

            if (bulkTariffSelect) {
                bulkTariffSelect.addEventListener('change', syncBulkCapacityFromSelect);
                syncBulkCapacityFromSelect();
            }

            // اعمال یکجای تعرفه و وزن به تمام واگن‌های تسویه‌نشده
            if (applyBulkBtn) {
                applyBulkBtn.addEventListener('click', function () {
                    const tariffId = bulkTariffSelect ? bulkTariffSelect.value : '';
                    const weightVal = bulkWeightInput ? bulkWeightInput.value : '';

                    document.querySelectorAll('.wagon-tariff-select').forEach(function (select) {
                        if (tariffId) {
                            select.value = tariffId;
                        }
                        const row = select.closest('tr');
                        const weightInput = row ? row.querySelector('.wagon-weight-input') : null;
                        if (weightInput) {
                            if (weightVal) {
                                weightInput.value = weightVal;
                            } else {
                                const opt = select.options[select.selectedIndex];
                                if (opt && opt.dataset.capacity) {
                                    weightInput.value = opt.dataset.capacity;
                                }
                            }
                            weightInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                });
            }

            // تکمیل ظرفیت کامل قرارداد برای تمام ردیف‌ها بر اساس تعرفه هر ردیف
            if (fillAllCapacityBtn) {
                fillAllCapacityBtn.addEventListener('click', function () {
                    document.querySelectorAll('.wagon-tariff-select').forEach(function (select) {
                        const opt = select.options[select.selectedIndex];
                        const row = select.closest('tr');
                        const weightInput = row ? row.querySelector('.wagon-weight-input') : null;
                        if (weightInput && opt && opt.dataset.capacity) {
                            weightInput.value = opt.dataset.capacity;
                            weightInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                });
            }

            // با تغییر تعرفه هر ردیف، ظرفیت کامل آن تعرفه در وزن همان ردیف (و ردیف‌های خالی بعدی) قرار می‌گیرد
            document.querySelectorAll('.wagon-tariff-select').forEach(function (select) {
                select.addEventListener('change', function () {
                    const opt = select.options[select.selectedIndex];
                    const row = select.closest('tr');
                    const weightInput = row ? row.querySelector('.wagon-weight-input') : null;

                    if (weightInput && opt && opt.dataset.capacity) {
                        weightInput.value = opt.dataset.capacity;
                        weightInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    // انتشار خودکار تعرفه به ردیف‌های بعدی که هنوز تعرفه ندارند
                    let nextRow = row ? row.nextElementSibling : null;
                    while (nextRow) {
                        const nextSelect = nextRow.querySelector('.wagon-tariff-select');
                        const nextWeight = nextRow.querySelector('.wagon-weight-input');
                        if (nextSelect && !nextSelect.value && select.value) {
                            nextSelect.value = select.value;
                            if (nextWeight && (!nextWeight.value || nextWeight.value.trim() === '') && opt && opt.dataset.capacity) {
                                nextWeight.value = opt.dataset.capacity;
                                nextWeight.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                        nextRow = nextRow.nextElementSibling;
                    }
                });
            });

            // دکمه «کامل» کنار وزن هر ردیف
            document.querySelectorAll('.fill-row-capacity-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = btn.closest('tr');
                    const select = row ? row.querySelector('.wagon-tariff-select') : null;
                    const weightInput = row ? row.querySelector('.wagon-weight-input') : null;
                    if (!weightInput) return;

                    const opt = select ? select.options[select.selectedIndex] : null;
                    weightInput.value = (opt && opt.dataset.capacity) ? opt.dataset.capacity : '65';
                    weightInput.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });

            // شرح کالا فقط برای واگنی که وزن دارد اجباری می‌شود
            document.querySelectorAll('input[data-loading-description]').forEach(function (input) {
                const row = input.closest('tr');
                const weight = row ? row.querySelector('input[name^="loading["][name$="[weight]"]') : null;

                if (! weight) {
                    return;
                }

                function refresh() {
                    const mustFill = weight.value.trim() !== '' && input.value.trim() === '';

                    input.required = mustFill;
                    input.setCustomValidity(mustFill ? 'شرح کالا برای این واگن الزامی است.' : '');
                }

                weight.addEventListener('input', refresh);
                input.addEventListener('input', refresh);
                refresh();
            });

            // با نوشتن شرح یک واگن، ردیف‌های بعدیِ خالی همان شرح را پیشنهاد می‌گیرند
            document.querySelectorAll('input[data-loading-description]').forEach(function (input) {
                input.addEventListener('input', function () {
                    let nextRow = input.closest('tr') ? input.closest('tr').nextElementSibling : null;

                    while (nextRow) {
                        const nextInput = nextRow.querySelector('input[data-loading-description]');

                        if (! nextInput) {
                            break;
                        }

                        if (nextInput.value.trim() === '') {
                            nextInput.value = input.value;
                            nextInput.dispatchEvent(new Event('input', { bubbles: true }));
                        } else {
                            break;
                        }

                        nextRow = nextRow.nextElementSibling;
                    }
                });
            });
            const loadingOwnerSelect = document.getElementById('loading_owner_id');
            document.querySelectorAll('#cargo-loading-form button[name="issue_billing"]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    const val = btn.value;
                    if ((val === '1' || val === 'print') && loadingOwnerSelect && !loadingOwnerSelect.value) {
                        e.preventDefault();
                        loadingOwnerSelect.focus();
                        alert('هنگام صدور صورتحساب، انتخاب نام صاحب کالا الزامی است.\n\nلطفاً از کادر «صاحب کالا» در بالای جدول، نام صاحب کالا را انتخاب کنید (یا اگر هنوز صاحب کالا مشخص نیست، دکمه «فقط ذخیره بارگیری» را بزنید).');
                    }
                });
            });
        });
    </script>
@endpush
