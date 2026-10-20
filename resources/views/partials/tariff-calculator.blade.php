@php
    $calcTaxPct = isset($taxPercentage) ? (float) $taxPercentage : (float) \App\Models\TaxSetting::currentPercentage();
    $calcTariffs = ($tariffs ?? collect())->where('is_active', true)->values();
@endphp

<div class="card border-info bg-light p-3 mb-4" data-tariff-calculator data-tax="{{ $calcTaxPct }}">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div>
            <h6 class="mb-0 fw-bold text-dark">
                محاسبه‌گر تعرفه و تناژ
                <span class="badge bg-info text-dark ms-1">صرفاً جهت محاسبه — بدون ثبت در سیستم</span>
            </h6>
            <div class="text-muted small mt-1">
                در این بخش می‌توانید تعرفه و هر میزان تناژ (حتی <strong>بیشتر از ظرفیت پایه تعرفه</strong>) را وارد کنید تا مبلغ دقیق به شما نمایش داده شود.
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary calc-reset-btn">
            پاک کردن محاسبه
        </button>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label small fw-bold mb-1">انتخاب تعرفه</label>
            <select class="form-select calc-tariff-select" data-searchable="true">
                <option value="">انتخاب تعرفه برای محاسبه…</option>
                @foreach($calcTariffs as $t)
                    <option value="{{ $t->id }}"
                            data-rate="{{ $t->rate }}"
                            data-capacity="{{ $t->capacity }}"
                            data-equipment="{{ $t->equipmentLabel() }}"
                            data-title="{{ $t->title }}">
                        {{ $t->title }} — ظرفیت پایه: {{ $t->capacity }} تن ({{ number_format($t->rate) }} ریال)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">
                تناژ جهت محاسبه (تن)
                <span class="text-primary">(بدون سقف ظرفیت)</span>
            </label>
            <input type="number"
                   step="0.01"
                   min="0.01"
                   class="form-control calc-tonnage-input"
                   placeholder="مثلاً 65 یا 80 (بیشتر از ظرفیت)">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">تعداد واگن</label>
            <input type="number"
                   step="1"
                   min="1"
                   value="1"
                   class="form-control calc-wagon-count-input"
                   placeholder="1">
        </div>
    </div>

    <div class="calc-result-box mt-3 pt-3 border-top d-none">
        <div class="alert alert-warning py-1 px-2 small mb-2 d-none calc-over-capacity-badge"></div>

        <div class="row g-2 text-center">
            <div class="col-md-2 col-6">
                <div class="bg-white border rounded p-2 h-100">
                    <div class="text-muted small">ظرفیت پایه تعرفه</div>
                    <div class="fw-bold mt-1"><span class="calc-out-capacity">0</span> تن</div>
                    <div class="small text-muted calc-out-equipment"></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="bg-white border rounded p-2 h-100">
                    <div class="text-muted small">مبلغ تعرفه در کتابچه</div>
                    <div class="fw-bold money mt-1"><span class="calc-out-booklet-rate">0</span></div>
                    <div class="small text-muted">ریال</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="bg-white border rounded p-2 h-100">
                    <div class="text-muted small">نرخ هر تن در کتابچه</div>
                    <div class="fw-bold money mt-1"><span class="calc-out-per-ton">0</span></div>
                    <div class="small text-muted">ریال / تن</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="bg-white border rounded p-2 h-100">
                    <div class="text-muted small">مبلغ پایه (بدون مالیات)</div>
                    <div class="fw-bold money text-primary mt-1"><span class="calc-out-base">0</span></div>
                    <div class="small text-muted">ریال</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="bg-white border rounded p-2 h-100">
                    <div class="text-muted small">مالیات ارزش افزوده ({{ $calcTaxPct }}٪)</div>
                    <div class="fw-bold money text-secondary mt-1"><span class="calc-out-tax">0</span></div>
                    <div class="small text-muted">ریال</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="bg-success-subtle border border-success rounded p-2 h-100">
                    <div class="text-success-emphasis small fw-bold">مبلغ کل با مالیات</div>
                    <div class="fw-bold money text-success fs-6 mt-1"><span class="calc-out-total">0</span></div>
                    <div class="small text-success-emphasis">ریال</div>
                </div>
            </div>
        </div>
    </div>
</div>
