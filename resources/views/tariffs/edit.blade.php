@extends('layouts.app')
@section('title', 'ویرایش تعرفه')

@section('content')
    <h4 class="mb-3">ویرایش تعرفه: {{ $tariff->code }}</h4>

    <form method="POST" action="{{ route('tariffs.update', $tariff) }}" class="card p-4">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label" for="code">کد</label>
                <input type="text" name="code" id="code" value="{{ old('code', $tariff->code) }}"
                       class="form-control @error('code') is-invalid @enderror" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label class="form-label" for="title">عنوان</label>
                <input type="text" name="title" id="title" value="{{ old('title', $tariff->title) }}"
                       class="form-control @error('title') is-invalid @enderror" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label class="form-label" for="unit">واحد (نمایشی)</label>
                <input type="text" name="unit" id="unit" value="{{ old('unit', $tariff->unit) }}"
                       class="form-control @error('unit') is-invalid @enderror" required>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="capacity">ظرفیت (تن)</label>
                <input type="number" step="0.01" min="0.1" max="200" name="capacity" id="capacity"
                       value="{{ old('capacity', $tariff->capacity) }}"
                       class="form-control @error('capacity') is-invalid @enderror" required>
                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="rate">نرخ کامل تعرفه (ریال)</label>
                <input type="number" min="0" step="1000" name="rate" id="rate" value="{{ old('rate', $tariff->rate) }}"
                       class="form-control @error('rate') is-invalid @enderror" required>
                @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category">دسته</label>
                <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                    @foreach(\App\Models\Tariff::CATEGORY_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('category', $tariff->category) === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="equipment_type">نوع دستگاه</label>
                <select name="equipment_type" id="equipment_type"
                        class="form-select @error('equipment_type') is-invalid @enderror" required>
                    @foreach($equipmentLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('equipment_type', $tariff->equipment_type) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('equipment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">بروزرسانی</button>
            <a href="{{ route('tariffs.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection
