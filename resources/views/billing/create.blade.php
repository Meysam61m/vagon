@extends('layouts.app')
@section('title', 'صدور صورتحساب')

@section('content')
    <h4 class="mb-3">صدور صورتحساب جدید</h4>

    {{-- مرحله اول: انتخاب صاحب کالا --}}
    <form method="GET" action="{{ route('billing.create') }}" class="card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="owner_id_filter">صاحب کالا</label>
                <select name="owner_id" id="owner_id_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">صاحب کالا را انتخاب کنید…</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) $ownerId === (string) $owner->id)>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if($ownerId)
        @if($wagons->isEmpty())
            <div class="alert alert-info">
                هیچ واگن بارگیری‌شده و تسویه‌نشده‌ای برای این صاحب کالا یافت نشد.
                ابتدا باید در بخش
                <a href="{{ route('cargo.index') }}">درخواست‌های بارگیری</a>
                وزن بارگیری ثبت شود.
            </div>
        @else
            @php
                $oldWagons = collect(old('wagons', []))->keyBy('id');
            @endphp

            <form method="POST" action="{{ route('billing.store') }}" class="card p-4" id="billing-form">
                @csrf
                <input type="hidden" name="owner_id" value="{{ $ownerId }}">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="representative_id">نماینده</label>
                        <select name="representative_id" id="representative_id" class="form-select">
                            <option value="">انتخاب کنید…</option>
                            @foreach($representatives as $representative)
                                <option value="{{ $representative->id }}" @selected((string) old('representative_id') === (string) $representative->id)>
                                    {{ $representative->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="billing_date_shamsi">تاریخ صدور (شمسی)</label>
                        <input type="text" name="billing_date_shamsi" id="billing_date_shamsi"
                               value="{{ old('billing_date_shamsi', $billingDateShamsi) }}"
                               class="form-control jalali-datepicker @error('billing_date_shamsi') is-invalid @enderror"
                               autocomplete="off" placeholder="۱۴۰۴/۰۷/۲۵" required>
                        @error('billing_date_shamsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">درصد مالیات جاری</label>
                        <input type="text" class="form-control" value="{{ $taxPercentage }}٪" disabled>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">واگن‌های بارگیری‌شده در انتظار تسویه</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all">
                        <label class="form-check-label" for="select-all">انتخاب همه</label>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered bg-white align-middle">
                        <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>شماره واگن</th>
                            <th>شماره درخواست</th>
                            <th>نماینده</th>
                            <th style="min-width: 220px;">تعرفه</th>
                            <th style="min-width: 120px;">وزن بارگیری (تن)</th>
                            <th>هزینه پایه</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($wagons as $index => $wagon)
                            @php
                                $old = $oldWagons->get($wagon->id);
                                $checked = $old !== null;
                                $selectedTariff = $old['tariff_id'] ?? $wagon->tariff_id;
                                $selectedWeight = $old['weight'] ?? $wagon->loaded_weight;
                                $capacity = $wagon->tariff?->capacity;
                            @endphp
                            <tr data-row="{{ $index }}">
                                <td>
                                    <input type="checkbox" class="form-check-input wagon-check"
                                           data-row="{{ $index }}" @checked($checked)>
                                    <input type="hidden" name="wagons[{{ $index }}][id]" value="{{ $wagon->id }}"
                                           data-row="{{ $index }}" @disabled(! $checked)>
                                </td>
                                <td>{{ $wagon->wagon_number }}</td>
                                <td>{{ $wagon->cargoRequest->request_number }}</td>
                                <td>{{ $wagon->cargoRequest->representative?->name ?? '-' }}</td>
                                <td>
                                    <select name="wagons[{{ $index }}][tariff_id]" class="form-select form-select-sm"
                                            data-row="{{ $index }}" @disabled(! $checked)>
                                        <option value="">انتخاب تعرفه…</option>
                                        @foreach($tariffs as $tariff)
                                            <option value="{{ $tariff->id }}"
                                                    data-rate="{{ $tariff->rate }}"
                                                    data-capacity="{{ $tariff->capacity }}"
                                                    @selected((string) $selectedTariff === (string) $tariff->id)>
                                                {{ $tariff->title }} — {{ number_format($tariff->rate) }} ریال / {{ $tariff->capacity }} تن
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01"
                                           max="{{ $capacity ?? 200 }}"
                                           name="wagons[{{ $index }}][weight]"
                                           value="{{ $selectedWeight }}"
                                           class="form-control form-control-sm weight-input"
                                           data-row="{{ $index }}" @disabled(! $checked)>
                                </td>
                                <td class="cost-preview money" data-row="{{ $index }}">-</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">جمع هزینه پایه</th>
                            <th class="money" id="totalBase">۰</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">مالیات ({{ $taxPercentage }}٪)</th>
                            <th class="money" id="totalTax">۰</th>
                        </tr>
                        <tr>
                            <th colspan="6" class="text-end">مبلغ نهایی</th>
                            <th class="money" id="totalFinal">۰</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="notes">توضیحات</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">صدور صورتحساب</button>
                    <a href="{{ route('billing.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const taxPercentage = {{ (float) $taxPercentage }};
            const form = document.getElementById('billing-form');

            if (!form) {
                return;
            }

            const rows = Array.from(document.querySelectorAll('tr[data-row]'));
            const format = (value) => Number(value || 0).toLocaleString('fa-IR');

            function rowElements(index) {
                return Array.from(document.querySelectorAll('[data-row="' + index + '"]'));
            }

            function toggleRow(index, enabled) {
                rowElements(index).forEach(function (element) {
                    // چک‌باکس هرگز غیرفعال نمی‌شود؛ در غیر این صورت انتخاب مجدد ممکن نیست
                    if (element.classList.contains('wagon-check')) {
                        return;
                    }

                    element.disabled = !enabled;
                });
            }

            function calculateRow(index) {
                const tariff = document.querySelector('select[data-row="' + index + '"]');
                const weight = document.querySelector('input.weight-input[data-row="' + index + '"]');
                const preview = document.querySelector('.cost-preview[data-row="' + index + '"]');
                const checkbox = document.querySelector('.wagon-check[data-row="' + index + '"]');

                if (!checkbox.checked) {
                    preview.textContent = '-';
                    return 0;
                }

                const option = tariff.selectedOptions[0];
                const rate = parseFloat(option?.dataset.rate || 0);
                const capacity = parseFloat(option?.dataset.capacity || 0);
                const value = parseFloat(weight.value || 0);

                let cost = 0;
                if (capacity > 0 && value > 0) {
                    cost = Math.round((value / capacity) * rate);
                }

                preview.textContent = format(cost);

                return cost;
            }

            function recalc() {
                let totalBase = 0;

                rows.forEach(function (row) {
                    totalBase += calculateRow(row.dataset.row);
                });

                const totalTax = Math.round(totalBase * (taxPercentage / 100));

                document.getElementById('totalBase').textContent = format(totalBase);
                document.getElementById('totalTax').textContent = format(totalTax);
                document.getElementById('totalFinal').textContent = format(totalBase + totalTax);
            }

            rows.forEach(function (row) {
                const index = row.dataset.row;
                const checkbox = document.querySelector('.wagon-check[data-row="' + index + '"]');
                const tariff = document.querySelector('select[data-row="' + index + '"]');
                const weight = document.querySelector('input.weight-input[data-row="' + index + '"]');

                checkbox.addEventListener('change', function () {
                    toggleRow(index, checkbox.checked);
                    recalc();
                });

                tariff.addEventListener('change', recalc);
                weight.addEventListener('input', recalc);

                const checked = checkbox.checked;
                toggleRow(index, checked);
            });

            document.getElementById('select-all').addEventListener('change', function (event) {
                rows.forEach(function (row) {
                    const index = row.dataset.row;
                    const checkbox = document.querySelector('.wagon-check[data-row="' + index + '"]');
                    checkbox.checked = event.target.checked;
                    toggleRow(index, checkbox.checked);
                });

                recalc();
            });

            recalc();
        });
    </script>
@endpush
