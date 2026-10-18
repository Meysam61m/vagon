@extends('layouts.app')
@section('title', 'افزودن تعرفه')

@section('content')
    @php
        $taxPct = $taxPercentage ?? 10;
    @endphp
    <h4 class="mb-3">افزودن تعرفه جدید</h4>

    <form method="POST" action="{{ route('tariffs.store') }}" class="card p-4">
        @csrf
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label" for="code">کد تعرفه</label>
                <input type="text" name="code" id="code" value="{{ old('code') }}"
                       class="form-control @error('code') is-invalid @enderror" placeholder="مثلاً T-1405-10" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="title">عنوان تعرفه / کالا</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror" placeholder="مثلاً بارگیری میلگرد با جرثقیل" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="unit">واحد (نمایشی)</label>
                <input type="text" name="unit" id="unit" value="{{ old('unit', 'واگن - ۶۵ تن') }}"
                       class="form-control @error('unit') is-invalid @enderror" required>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="capacity">ظرفیت واگن (تن)</label>
                <input type="number" step="0.01" min="0.1" max="200" name="capacity" id="capacity"
                       value="{{ old('capacity', 65) }}" class="form-control @error('capacity') is-invalid @enderror" required>
                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="rate">مبلغ تعرفه در کتابچه - بدون مالیات (ریال)</label>
                <input type="number" min="0" step="1" name="rate" id="rate" value="{{ old('rate') }}"
                       class="form-control @error('rate') is-invalid @enderror" required>
                @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="rate_with_vat">مبلغ با احتساب مالیات ({{ $taxPct }}٪) (کمکی)</label>
                <input type="number" min="0" step="1" id="rate_with_vat"
                       class="form-control" placeholder="با وارد کردن، نرخ کتابچه محاسبه می‌شود">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="equipment_type">نوع دستگاه (وسیله حمل)</label>
                <select name="equipment_type" id="equipment_type"
                        class="form-select @error('equipment_type') is-invalid @enderror" required>
                    @foreach($equipmentLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('equipment_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('equipment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="category">دسته عملیاتی</label>
                <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                    @foreach(\App\Models\Tariff::CATEGORY_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('category', 1) === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">ذخیره تعرفه</button>
            <a href="{{ route('tariffs.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const taxDivisor = {{ 1 + (($taxPercentage ?? 10) / 100) }};
            const rateInput = document.getElementById('rate');
            const vatInput = document.getElementById('rate_with_vat');

            if (rateInput && vatInput) {
                if (rateInput.value) {
                    vatInput.value = Math.round(Number(rateInput.value) * taxDivisor);
                }

                rateInput.addEventListener('input', function () {
                    const v = Number(rateInput.value);
                    vatInput.value = v > 0 ? Math.round(v * taxDivisor) : '';
                });

                vatInput.addEventListener('input', function () {
                    const v = Number(vatInput.value);
                    rateInput.value = v > 0 ? Math.round(v / taxDivisor) : '';
                });
            }
        });
    </script>
@endpush
