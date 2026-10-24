@extends('layouts.app')
@section('title', 'ثبت درخواست بارگیری')

@section('content')
    @php
        $selectedOwnerId = old('owner_id', session('selected_owner_id'));
        $selectedRepId = old('representative_id', session('selected_representative_id'));
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">ثبت درخواست بارگیری جدید</h4>
            <div class="text-muted small">
                در زمان بارگیری ثبت <strong>صاحب کالا اختیاری است (می‌توانید «ناشناس» بگذارید)</strong> و فقط انتخاب <strong>نماینده</strong> الزامی است. نام صاحب کالا هنگام صدور صورتحساب الزامی خواهد بود.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button type="button"
                    class="btn btn-outline-primary btn-sm fw-semibold"
                    data-bs-toggle="modal"
                    data-bs-target="#quickOwnerModal"
                    title="میانبر کیبورد: Alt + O">
                + تعریف صاحب کالا <kbd class="ms-1 small">Alt+O</kbd>
            </button>
            <button type="button"
                    class="btn btn-outline-success btn-sm fw-semibold"
                    data-bs-toggle="modal"
                    data-bs-target="#quickRepModal"
                    title="میانبر کیبورد: Alt + R">
                + تعریف نماینده <kbd class="ms-1 small">Alt+R</kbd>
            </button>
            <a href="{{ route('owners.index') }}" class="btn btn-outline-secondary btn-sm">مدیریت صاحبان کالا</a>
            <a href="{{ route('representatives.index') }}" class="btn btn-outline-secondary btn-sm">مدیریت نمایندگان</a>
            <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت به کارتابل</a>
        </div>
    </div>

    <datalist id="common-cargo-descriptions">
        <option value="میلگرد"></option>
        <option value="کلاف"></option>
        <option value="ناودونی"></option>
        <option value="نبشی"></option>
        <option value="تسمه-ناودونی"></option>
        <option value="سود پرک"></option>
        <option value="سیمان جامبو"></option>
        <option value="کاشی"></option>
        <option value="کاشی-چینی"></option>
        <option value="شیر خشک"></option>
        <option value="سنگ"></option>
        <option value="سنگ ساختمانی"></option>
        <option value="کروم"></option>
        <option value="جوش شیرین"></option>
        <option value="رزین"></option>
        <option value="ام دی اف"></option>
        <option value="شیشه"></option>
        <option value="سوله"></option>
        <option value="کابل"></option>
    </datalist>

    <form method="POST" action="{{ route('cargo.store') }}" class="card p-4" id="cargo-create-form">
        @csrf

        <div class="row g-3">
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                    <label class="form-label mb-0" for="owner_id_input">
                        صاحب کالا
                        <span class="badge bg-warning-subtle text-dark border small fw-normal">اختیاری هنگام بارگیری</span>
                    </label>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-info-subtle text-dark border small" id="owner-last-cargo-hint" style="display: none;"></span>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary py-0 px-2"
                                style="font-size: 0.78rem;"
                                data-bs-toggle="modal"
                                data-bs-target="#quickOwnerModal"
                                title="تعریف سریع صاحب کالا جدید (Alt+O)">
                            + صاحب کالا جدید
                        </button>
                    </div>
                </div>
                <select name="owner_id" id="owner_id_input" class="form-select @error('owner_id') is-invalid @enderror">
                    <option value="">— ناشناس / نامشخص (تعیین در زمان صدور صورتحساب) —</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected($selectedOwnerId == $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
                @error('owner_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text small text-muted">
                    اگر نام صاحب کالا هنوز مشخص نیست، گزینه «ناشناس» را انتخاب کنید.
                </div>
            </div>

            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                    <label class="form-label mb-0" for="representative_id">نماینده <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-light text-secondary border small" id="rep-auto-hint">همه نمایندگان فعال</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-success py-0 px-2"
                                style="font-size: 0.78rem;"
                                data-bs-toggle="modal"
                                data-bs-target="#quickRepModal"
                                title="تعریف سریع نماینده جدید (Alt+R)">
                            + نماینده جدید
                        </button>
                    </div>
                </div>
                <select name="representative_id" id="representative_id" class="form-select @error('representative_id') is-invalid @enderror" required>
                    <option value="">انتخاب نماینده (اجباری)…</option>
                    @foreach($representatives as $rep)
                        <option value="{{ $rep->id }}"
                                data-owners="{{ $rep->owners->pluck('id')->implode(',') }}"
                                @selected($selectedRepId == $rep->id)>
                            {{ $rep->name }}
                        </option>
                    @endforeach
                </select>
                @error('representative_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1" for="request_date_shamsi_input">تاریخ درخواست (شمسی) <span class="text-danger">*</span></label>
                <input type="text" name="request_date_shamsi" id="request_date_shamsi_input"
                       value="{{ old('request_date_shamsi', $requestDateShamsi) }}"
                       placeholder="مثلاً ۱۴۰۵/۰۱/۲۰"
                       class="form-control jalali-datepicker" autocomplete="off" required>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div>
                <h6 class="mb-0">
                    واگن‌ها و شرح کالا
                    <span class="badge bg-primary ms-1" id="wagon-counter-badge">۱ واگن</span>
                    <span class="badge bg-danger ms-1 d-none" id="duplicate-warning-badge">شماره واگن تکراری!</span>
                </h6>
                <small class="text-muted">
                    شرح کالا از واگن قبلی پیشنهاد می‌شود. با زدن کلید <kbd>Enter</kbd> در آخرین ردیف، واگن بعدی خودکار اضافه می‌شود.
                </small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="add-wagon" class="btn btn-sm btn-outline-primary">+ افزودن واگن (Enter)</button>
            </div>
        </div>

        <div id="wagon-rows">
            @php $oldWagons = old('wagons', [['number' => '', 'description' => '', 'line_number' => '']]); @endphp
            @foreach($oldWagons as $index => $w)
                <div class="row g-2 mb-2 wagon-row align-items-start">
                    <div class="col-auto pt-2 text-muted small fw-bold wagon-row-num" style="min-width: 28px;">
                        {{ $index + 1 }}.
                    </div>
                    <div class="col-md-3">
                        <input type="text"
                               name="wagons[{{ $index }}][number]"
                               value="{{ $w['number'] ?? '' }}"
                               class="form-control font-monospace @error('wagons.'.$index.'.number') is-invalid @enderror"
                               data-wagon-number
                               placeholder="شماره واگن (مثلاً 663532)"
                               required>
                        <div class="invalid-feedback" data-dup-feedback>
                            @error('wagons.'.$index.'.number') {{ $message }} @else شماره واگن در همین فرم تکراری است! @enderror
                        </div>
                        <div class="small text-warning-emphasis mt-1 d-none" data-unsettled-warning></div>
                    </div>
                    <div class="col-md-4">
                        <input type="text"
                               name="wagons[{{ $index }}][description]"
                               value="{{ $w['description'] ?? '' }}"
                               class="form-control @error('wagons.'.$index.'.description') is-invalid @enderror"
                               data-wagon-description
                               list="common-cargo-descriptions"
                               placeholder="شرح کالا (اجباری — مثلاً میلگرد، کاشی)"
                               required>
                        @error('wagons.'.$index.'.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <input type="text"
                               name="wagons[{{ $index }}][line_number]"
                               value="{{ $w['line_number'] ?? '' }}"
                               class="form-control @error('wagons.'.$index.'.line_number') is-invalid @enderror"
                               data-wagon-line
                               placeholder="شماره خط (مثلاً خط ۲)">
                        @error('wagons.'.$index.'.line_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100 remove-wagon" tabindex="-1">حذف</button>
                    </div>
                </div>
            @endforeach
        </div>

        <hr class="my-4">

        <div class="mb-3">
            <label class="form-label" for="notes_input">توضیحات</label>
            <textarea name="notes" id="notes_input" class="form-control" rows="2" placeholder="توضیحات تکمیلی در صورت نیاز…">{{ old('notes') }}</textarea>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="btn btn-primary fw-semibold px-4" type="submit">
                ثبت درخواست بارگیری
            </button>
            <a href="{{ route('cargo.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>

    {{-- مودال تعریف سریع صاحب کالا (میانبر Alt+O) --}}
    <div class="modal fade" id="quickOwnerModal" tabindex="-1" aria-labelledby="quickOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('owners.store') }}" class="modal-content">
                @csrf
                <input type="hidden" name="redirect_to" value="cargo.create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="quickOwnerModalLabel">تعریف سریع صاحب کالا جدید</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="quick_owner_name">نام صاحب کالا <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="quick_owner_name" class="form-control" required placeholder="نام شخص حقیقی یا شرکت">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="quick_owner_national_id">کد ملی / شناسه ملی</label>
                            <input type="text" name="national_id" id="quick_owner_national_id" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="quick_owner_phone">تلفن</label>
                            <input type="text" name="phone" id="quick_owner_phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="quick_owner_economic_code">کد اقتصادی</label>
                            <input type="text" name="economic_code" id="quick_owner_economic_code" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="quick_owner_address">آدرس</label>
                            <input type="text" name="address" id="quick_owner_address" class="form-control">
                        </div>
                        @if($representatives->isNotEmpty())
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <label class="form-label mb-0">نمایندگان مرتبط با این صاحب کالا (اختیاری)</label>
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           style="max-width: 240px;"
                                           data-owner-checkbox-search="#quick-owner-reps-list"
                                           placeholder="جستجوی نام نماینده…">
                                </div>
                                <div class="row g-2 border rounded p-2 bg-light" id="quick-owner-reps-list" style="max-height: 180px; overflow-y: auto;">
                                    @foreach($representatives as $rep)
                                        <div class="col-md-4" data-owner-checkbox-item>
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input quick-owner-rep-check"
                                                       name="representative_ids[]"
                                                       value="{{ $rep->id }}"
                                                       id="quick_owner_rep_{{ $rep->id }}">
                                                <label class="form-check-label small" for="quick_owner_rep_{{ $rep->id }}">{{ $rep->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="{{ route('owners.create', ['redirect_to' => 'cargo.create']) }}" class="btn btn-link btn-sm text-decoration-none">
                        باز کردن فرم کامل تعریف صاحب کالا
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary fw-semibold">ذخیره و انتخاب در فرم</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تعریف سریع نماینده (میانبر Alt+R) --}}
    <div class="modal fade" id="quickRepModal" tabindex="-1" aria-labelledby="quickRepModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('representatives.store') }}" class="modal-content">
                @csrf
                <input type="hidden" name="redirect_to" value="cargo.create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="quickRepModalLabel">تعریف سریع نماینده جدید</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="quick_rep_name">نام نماینده <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="quick_rep_name" class="form-control" required placeholder="نام و نام خانوادگی نماینده">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="quick_rep_national_id">کد ملی</label>
                            <input type="text" name="national_id" id="quick_rep_national_id" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="quick_rep_phone">تلفن</label>
                            <input type="text" name="phone" id="quick_rep_phone" class="form-control">
                        </div>
                        @if($owners->isNotEmpty())
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <label class="form-label mb-0">صاحبان کالای مرتبط با این نماینده (اختیاری)</label>
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           style="max-width: 240px;"
                                           data-owner-checkbox-search="#quick-rep-owners-list"
                                           placeholder="جستجوی نام صاحب کالا…">
                                </div>
                                <div class="row g-2 border rounded p-2 bg-light" id="quick-rep-owners-list" style="max-height: 180px; overflow-y: auto;">
                                    @foreach($owners as $owner)
                                        <div class="col-md-4" data-owner-checkbox-item>
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       class="form-check-input quick-rep-owner-check"
                                                       name="owner_ids[]"
                                                       value="{{ $owner->id }}"
                                                       id="quick_rep_owner_{{ $owner->id }}">
                                                <label class="form-check-label small" for="quick_rep_owner_{{ $owner->id }}">{{ $owner->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="{{ route('representatives.create', ['redirect_to' => 'cargo.create']) }}" class="btn btn-link btn-sm text-decoration-none">
                        باز کردن فرم کامل تعریف نماینده
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-success fw-semibold">ذخیره و انتخاب در فرم</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const container = document.getElementById('wagon-rows');
            const addBtn = document.getElementById('add-wagon');
            const ownerSelect = document.getElementById('owner_id_input');
            const repSelect = document.getElementById('representative_id');
            const repHint = document.getElementById('rep-auto-hint');
            const ownerLastHint = document.getElementById('owner-last-cargo-hint');
            const wagonCounterBadge = document.getElementById('wagon-counter-badge');
            const dupWarningBadge = document.getElementById('duplicate-warning-badge');
            const quickTariffSelect = document.getElementById('quick_tariff_id');
            const quickWeightInput = document.getElementById('quick_weight');
            const quickSuggestBadge = document.getElementById('quick-tariff-suggest-badge');
            const quickActionInput = document.getElementById('quick_action_input');

            // داده‌های پیش‌فرض آخرین درخواست هر صاحب کالا، هر نماینده و لیست واگن‌های تسویه‌نشده
            const ownerDefaults = @json($ownerDefaults ?? []);
            const repDefaults = @json($repDefaults ?? []);
            const unsettledSet = new Set(@json($unsettledWagonNumbers ?? []));

            // ۱. فیلتر و انتخاب خودکار نماینده + پیشنهاد آخرین کالا و تعرفه صاحب کالا/نماینده
            const allRepOptions = Array.from(repSelect.querySelectorAll('option[data-owners]')).map(opt => ({
                value: opt.value,
                label: opt.textContent.trim(),
                owners: (opt.getAttribute('data-owners') || '').split(',').map(s => s.trim()).filter(Boolean)
            }));

            function applySuggestedCargoAndTariff(def) {
                if (!def || !def.description) return;
                if (ownerLastHint) {
                    ownerLastHint.textContent = 'آخرین محموله: ' + def.description;
                    ownerLastHint.style.display = 'inline-block';
                }
                const firstDesc = container.querySelector('input[data-wagon-description]');
                if (firstDesc && (firstDesc.value.trim() === '' || firstDesc.dataset.autoSuggested === '1')) {
                    firstDesc.value = def.description;
                    firstDesc.dataset.autoSuggested = '1';
                    propagateSuggestionFrom(firstDesc.closest('.wagon-row'));
                }
                if (def.tariff_id && quickTariffSelect && quickTariffSelect.dataset.userSelected !== '1') {
                    quickTariffSelect.value = String(def.tariff_id);
                    const opt = quickTariffSelect.options[quickTariffSelect.selectedIndex];
                    if (opt && opt.dataset.capacity) {
                        quickWeightInput.value = opt.dataset.capacity;
                        quickWeightInput.dataset.autoCapacity = '1';
                    }
                    if (quickSuggestBadge) {
                        quickSuggestBadge.style.display = 'inline-block';
                    }
                }
            }

            function syncRepresentativesAndDefaults(preserveSelected) {
                const ownerId = ownerSelect.value;
                const currentRep = repSelect.value;
                const def = ownerId && ownerDefaults[ownerId] ? ownerDefaults[ownerId] : null;

                while (repSelect.options.length > 1) {
                    repSelect.remove(1);
                }

                const linkedToOwner = ownerId
                    ? allRepOptions.filter(r => r.owners.includes(ownerId))
                    : allRepOptions;

                const optionsToShow = ownerId ? linkedToOwner : allRepOptions;

                optionsToShow.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.value;
                    opt.textContent = r.label;
                    opt.setAttribute('data-owners', r.owners.join(','));
                    repSelect.appendChild(opt);
                });

                if (preserveSelected && currentRep && optionsToShow.some(r => r.value === currentRep)) {
                    repSelect.value = currentRep;
                } else if (ownerId && linkedToOwner.length === 1) {
                    repSelect.value = linkedToOwner[0].value;
                    if (repHint) {
                        repHint.textContent = '✓ نماینده خودکار انتخاب شد';
                        repHint.className = 'badge bg-success-subtle text-success border border-success-subtle small';
                    }
                } else if (def && def.representative_id && optionsToShow.some(r => r.value === String(def.representative_id))) {
                    repSelect.value = String(def.representative_id);
                    if (repHint) {
                        repHint.textContent = '✓ آخرین نماینده این صاحب کالا انتخاب شد';
                        repHint.className = 'badge bg-success-subtle text-success border border-success-subtle small';
                    }
                } else if (currentRep && optionsToShow.some(r => r.value === currentRep)) {
                    repSelect.value = currentRep;
                } else if (ownerId && repHint) {
                    repHint.textContent = linkedToOwner.length > 0
                        ? (linkedToOwner.length + ' نماینده مرتبط')
                        : 'بدون نماینده تخصیص‌یافته';
                    repHint.className = linkedToOwner.length > 0
                        ? 'badge bg-info-subtle text-dark border small'
                        : 'badge bg-danger-subtle text-danger border small';
                } else if (!ownerId && repHint) {
                    repHint.textContent = 'همه نمایندگان فعال (صاحب کالا: ناشناس)';
                    repHint.className = 'badge bg-warning-subtle text-dark border small';
                }

                if (!preserveSelected && def && def.description) {
                    applySuggestedCargoAndTariff(def);
                } else if (!ownerId) {
                    const rDef = repSelect.value && repDefaults[repSelect.value] ? repDefaults[repSelect.value] : null;
                    if (rDef && rDef.description) {
                        applySuggestedCargoAndTariff(rDef);
                    } else if (ownerLastHint) {
                        ownerLastHint.style.display = 'none';
                    }
                }
            }

            ownerSelect.addEventListener('change', function () {
                syncRepresentativesAndDefaults(false);
            });

            repSelect.addEventListener('change', function () {
                const rId = repSelect.value;
                if (!ownerSelect.value && rId && repDefaults[rId]) {
                    applySuggestedCargoAndTariff(repDefaults[rId]);
                }
            });

            syncRepresentativesAndDefaults(true);

            // ۲. شماره‌گذاری ردیف‌ها و بررسی لحظه‌ای شماره‌واگن تکراری یا تسویه‌نشده
            function validateWagonNumbersRealtime() {
                const rows = Array.from(container.querySelectorAll('.wagon-row'));
                const counts = new Map();

                rows.forEach((row, i) => {
                    const numEl = row.querySelector('.wagon-row-num');
                    if (numEl) {
                        numEl.textContent = (i + 1) + '.';
                    }
                    const inp = row.querySelector('input[data-wagon-number]');
                    if (inp) {
                        const val = inp.value.trim();
                        if (val !== '') {
                            counts.set(val, (counts.get(val) || 0) + 1);
                        }
                    }
                });

                let hasDuplicate = false;
                rows.forEach(row => {
                    const inp = row.querySelector('input[data-wagon-number]');
                    const unsettledWarn = row.querySelector('[data-unsettled-warning]');
                    if (!inp) return;

                    const val = inp.value.trim();
                    const isDup = val !== '' && (counts.get(val) || 0) > 1;

                    if (isDup) {
                        hasDuplicate = true;
                        inp.classList.add('is-invalid');
                        inp.setCustomValidity('شماره واگن تکراری است.');
                    } else {
                        inp.classList.remove('is-invalid');
                        inp.setCustomValidity('');
                    }

                    if (unsettledWarn) {
                        if (val !== '' && unsettledSet.has(val)) {
                            unsettledWarn.textContent = '⚠ این شماره واگن در یک درخواست باز (تسویه‌نشده) دیگر هم وجود دارد.';
                            unsettledWarn.classList.remove('d-none');
                        } else {
                            unsettledWarn.textContent = '';
                            unsettledWarn.classList.add('d-none');
                        }
                    }
                });

                if (wagonCounterBadge) {
                    wagonCounterBadge.textContent = rows.length + ' واگن';
                }
                if (dupWarningBadge) {
                    dupWarningBadge.classList.toggle('d-none', !hasDuplicate);
                }
            }

            // ۳. پیشنهاد خودکار تعرفه از روی شرح کالا
            const keywordCodeMap = [
                { code: 'T-1405-01', keywords: ['میلگرد', 'کلاف', 'ناودونی', 'نبشی', 'تسمه', 'سوله', 'شیشه', 'آهن', 'ورق', 'تیرآهن'] },
                { code: 'T-1405-02', keywords: ['سود پرک', 'سودپرک'] },
                { code: 'T-1405-03', keywords: ['سیمان', 'جامبو'] },
                { code: 'T-1405-04', keywords: ['کاشی', 'چینی', 'سرامیک', 'شیر خشک', 'شیرخشک', 'آبمیوه', 'ابمیوه', 'کابل'] },
                { code: 'T-1405-05', keywords: ['سنگ ساختمانی', 'سنگ'] },
                { code: 'T-1405-06', keywords: ['کروم', 'جوش شیرین'] },
                { code: 'T-1405-08', keywords: ['ام دی اف', 'MDF', 'mdf', 'نئوپان'] },
                { code: 'T-1405-09', keywords: ['رزین'] }
            ];

            function suggestQuickTariffFromDescription(descText) {
                if (!quickTariffSelect || quickTariffSelect.dataset.userSelected === '1') {
                    return;
                }
                const norm = (descText || '').trim();
                if (!norm) {
                    return;
                }
                for (const item of keywordCodeMap) {
                    if (item.keywords.some(kw => norm.includes(kw))) {
                        const opt = Array.from(quickTariffSelect.options).find(o => o.dataset.code === item.code);
                        if (opt) {
                            quickTariffSelect.value = opt.value;
                            if (opt.dataset.capacity && (!quickWeightInput.value || quickWeightInput.dataset.autoCapacity === '1')) {
                                quickWeightInput.value = opt.dataset.capacity;
                                quickWeightInput.dataset.autoCapacity = '1';
                            }
                            if (quickSuggestBadge) {
                                quickSuggestBadge.style.display = 'inline-block';
                            }
                            return;
                        }
                    }
                }
            }

            if (quickTariffSelect) {
                quickTariffSelect.addEventListener('change', function () {
                    quickTariffSelect.dataset.userSelected = quickTariffSelect.value ? '1' : '0';
                    const opt = quickTariffSelect.options[quickTariffSelect.selectedIndex];
                    if (opt && opt.dataset.capacity) {
                        quickWeightInput.value = opt.dataset.capacity;
                        quickWeightInput.dataset.autoCapacity = '1';
                    }
                });
            }

            const fillCapacityBtn = document.getElementById('fill-contract-capacity-btn');
            if (fillCapacityBtn && quickTariffSelect && quickWeightInput) {
                fillCapacityBtn.addEventListener('click', function () {
                    const opt = quickTariffSelect.options[quickTariffSelect.selectedIndex];
                    quickWeightInput.value = (opt && opt.dataset.capacity) ? opt.dataset.capacity : '65';
                });
            }

            const clearQuickBtn = document.getElementById('clear-quick-tariff');
            if (clearQuickBtn && quickTariffSelect && quickWeightInput) {
                clearQuickBtn.addEventListener('click', function () {
                    quickTariffSelect.value = '';
                    quickTariffSelect.dataset.userSelected = '0';
                    quickWeightInput.value = '';
                    if (quickSuggestBadge) {
                        quickSuggestBadge.style.display = 'none';
                    }
                });
            }

            function getPreviousDescription(row) {
                let prev = row ? row.previousElementSibling : container.lastElementChild;
                while (prev) {
                    const input = prev.querySelector('input[data-wagon-description]');
                    if (input && input.value.trim() !== '') {
                        return input.value;
                    }
                    prev = prev.previousElementSibling;
                }
                return '';
            }

            function getPreviousLineNumber(row) {
                let prev = row ? row.previousElementSibling : container.lastElementChild;
                while (prev) {
                    const input = prev.querySelector('input[data-wagon-line]');
                    if (input && input.value.trim() !== '') {
                        return input.value;
                    }
                    prev = prev.previousElementSibling;
                }
                return '';
            }

            function propagateSuggestionFrom(row) {
                if (!row) return;
                const sourceInput = row.querySelector('input[data-wagon-description]');
                if (!sourceInput) {
                    return;
                }
                const value = sourceInput.value;
                suggestQuickTariffFromDescription(value);

                let next = row.nextElementSibling;
                while (next) {
                    const nextInput = next.querySelector('input[data-wagon-description]');
                    if (!nextInput) {
                        break;
                    }
                    if (nextInput.dataset.autoSuggested === '1' || nextInput.value.trim() === '') {
                        nextInput.value = value;
                        nextInput.dataset.autoSuggested = value.trim() !== '' ? '1' : '0';
                    } else {
                        break;
                    }
                    next = next.nextElementSibling;
                }
            }

            function bindRowInputs(row) {
                const descInput = row.querySelector('input[data-wagon-description]');
                const lineInput = row.querySelector('input[data-wagon-line]');
                const numInput = row.querySelector('input[data-wagon-number]');

                if (numInput) {
                    numInput.addEventListener('input', validateWagonNumbersRealtime);
                }

                if (descInput) {
                    descInput.addEventListener('input', function () {
                        descInput.dataset.autoSuggested = '0';
                        propagateSuggestionFrom(row);
                    });
                }

                [numInput, descInput, lineInput].forEach(inp => {
                    if (!inp) return;
                    inp.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (inp === numInput && descInput && descInput.value.trim() === '') {
                                descInput.focus();
                                return;
                            }
                            const nextRow = row.nextElementSibling;
                            if (nextRow) {
                                const nextNum = nextRow.querySelector('input[data-wagon-number]');
                                if (nextNum) nextNum.focus();
                            } else {
                                const createdRow = createWagonRow('', '');
                                const newNum = createdRow.querySelector('input[data-wagon-number]');
                                if (newNum) newNum.focus();
                            }
                        }
                    });
                });
            }

            container.querySelectorAll('.wagon-row').forEach(bindRowInputs);
            validateWagonNumbersRealtime();

            let nextIndex = container.querySelectorAll('.wagon-row').length;

            function createWagonRow(wagonNumber, cargoDescription) {
                const index = nextIndex++;
                const suggestedDescription = cargoDescription || getPreviousDescription(null);
                const suggestedLine = getPreviousLineNumber(null);
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 wagon-row align-items-start';

                const numCol = document.createElement('div');
                numCol.className = 'col-auto pt-2 text-muted small fw-bold wagon-row-num';
                numCol.style.minWidth = '28px';

                const colNumber = document.createElement('div');
                colNumber.className = 'col-md-3';
                const numberInput = document.createElement('input');
                numberInput.type = 'text';
                numberInput.name = 'wagons[' + index + '][number]';
                numberInput.className = 'form-control font-monospace';
                numberInput.setAttribute('data-wagon-number', '');
                numberInput.placeholder = 'شماره واگن (مثلاً 663532)';
                numberInput.required = true;
                numberInput.value = wagonNumber || '';
                const dupFeedback = document.createElement('div');
                dupFeedback.className = 'invalid-feedback';
                dupFeedback.setAttribute('data-dup-feedback', '');
                dupFeedback.textContent = 'شماره واگن در همین فرم تکراری است!';
                const unsettledWarn = document.createElement('div');
                unsettledWarn.className = 'small text-warning-emphasis mt-1 d-none';
                unsettledWarn.setAttribute('data-unsettled-warning', '');
                colNumber.appendChild(numberInput);
                colNumber.appendChild(dupFeedback);
                colNumber.appendChild(unsettledWarn);

                const colDesc = document.createElement('div');
                colDesc.className = 'col-md-4';
                const descInput = document.createElement('input');
                descInput.type = 'text';
                descInput.name = 'wagons[' + index + '][description]';
                descInput.className = 'form-control';
                descInput.setAttribute('data-wagon-description', '');
                descInput.setAttribute('list', 'common-cargo-descriptions');
                descInput.placeholder = 'شرح کالا (اجباری — مثلاً میلگرد، کاشی)';
                descInput.required = true;
                descInput.value = suggestedDescription;
                descInput.dataset.autoSuggested = (!cargoDescription && suggestedDescription !== '') ? '1' : '0';
                colDesc.appendChild(descInput);

                const colLine = document.createElement('div');
                colLine.className = 'col-md-2';
                const lineInput = document.createElement('input');
                lineInput.type = 'text';
                lineInput.name = 'wagons[' + index + '][line_number]';
                lineInput.className = 'form-control';
                lineInput.setAttribute('data-wagon-line', '');
                lineInput.placeholder = 'شماره خط (مثلاً خط ۲)';
                lineInput.value = suggestedLine;
                colLine.appendChild(lineInput);

                const colBtn = document.createElement('div');
                colBtn.className = 'col-md-2';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-outline-danger w-100 remove-wagon';
                removeBtn.tabIndex = -1;
                removeBtn.textContent = 'حذف';
                colBtn.appendChild(removeBtn);

                row.appendChild(numCol);
                row.appendChild(colNumber);
                row.appendChild(colDesc);
                row.appendChild(colLine);
                row.appendChild(colBtn);
                container.appendChild(row);

                bindRowInputs(row);
                validateWagonNumbersRealtime();
                if (suggestedDescription) {
                    suggestQuickTariffFromDescription(suggestedDescription);
                }
                return row;
            }

            addBtn.addEventListener('click', function () {
                const row = createWagonRow('', '');
                const numInput = row.querySelector('input[data-wagon-number]');
                if (numInput) numInput.focus();
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-wagon')) {
                    const row = e.target.closest('.wagon-row');
                    const rows = container.querySelectorAll('.wagon-row');
                    if (rows.length > 1) {
                        row.remove();
                        validateWagonNumbersRealtime();
                    } else if (row) {
                        const numInp = row.querySelector('input[data-wagon-number]');
                        const descInp = row.querySelector('input[data-wagon-description]');
                        const lineInp = row.querySelector('input[data-wagon-line]');
                        if (numInp) numInp.value = '';
                        if (descInp) descInp.value = '';
                        if (lineInp) lineInp.value = '';
                        validateWagonNumbersRealtime();
                    }
                }
            });

            // ۴. مودال‌های تعریف سریع صاحب کالا و نماینده + کلیدهای میانبر (Alt+O و Alt+R)
            const quickOwnerModalEl = document.getElementById('quickOwnerModal');
            const quickRepModalEl = document.getElementById('quickRepModal');

            if (quickOwnerModalEl) {
                quickOwnerModalEl.addEventListener('show.bs.modal', function () {
                    const currentRepId = repSelect ? repSelect.value : '';
                    quickOwnerModalEl.querySelectorAll('.quick-owner-rep-check').forEach(function (chk) {
                        chk.checked = (currentRepId !== '' && String(chk.value) === String(currentRepId));
                    });
                });
                quickOwnerModalEl.addEventListener('shown.bs.modal', function () {
                    const nameInput = document.getElementById('quick_owner_name');
                    if (nameInput) nameInput.focus();
                });
            }

            if (quickRepModalEl) {
                quickRepModalEl.addEventListener('show.bs.modal', function () {
                    const currentOwnerId = ownerSelect ? ownerSelect.value : '';
                    quickRepModalEl.querySelectorAll('.quick-rep-owner-check').forEach(function (chk) {
                        chk.checked = (currentOwnerId !== '' && String(chk.value) === String(currentOwnerId));
                    });
                });
                quickRepModalEl.addEventListener('shown.bs.modal', function () {
                    const nameInput = document.getElementById('quick_rep_name');
                    if (nameInput) nameInput.focus();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (!e.altKey || e.ctrlKey || e.metaKey) return;
                const code = e.code || '';
                const key = (e.key || '').toLowerCase();
                if (code === 'KeyO' || key === 'o' || key === 'خ') {
                    e.preventDefault();
                    if (quickOwnerModalEl && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(quickOwnerModalEl).show();
                    }
                } else if (code === 'KeyR' || key === 'r' || key === 'ق') {
                    e.preventDefault();
                    if (quickRepModalEl && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(quickRepModalEl).show();
                    }
                }
            });
        })();
    </script>
@endpush
