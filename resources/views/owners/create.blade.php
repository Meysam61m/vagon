@extends('layouts.app')
@section('title', 'افزودن صاحب کالا')

@section('content')
    <h4 class="mb-3">افزودن صاحب کالای جدید</h4>

    @php
        $defaultReps = old('representative_ids', request()->filled('representative_id') ? [(int) request('representative_id')] : []);
        $cancelUrl = match (request('redirect_to')) {
            'cargo.create' => route('cargo.create'),
            'cargo.index' => route('cargo.index'),
            'cargo.show' => request('cargo_request_id') ? route('cargo.show', request('cargo_request_id')) : route('cargo.index'),
            'billing.create' => route('billing.create', array_filter(['cargo_request_id' => request('cargo_request_id')])),
            default => route('owners.index'),
        };
    @endphp

    <form method="POST" action="{{ route('owners.store') }}" class="card p-4">
        @csrf
        @if(request('redirect_to'))
            <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
        @endif
        @if(request('cargo_request_id'))
            <input type="hidden" name="cargo_request_id" value="{{ request('cargo_request_id') }}">
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">نام</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="national_id">کد ملی / شناسه ملی</label>
                <input type="text" name="national_id" id="national_id" value="{{ old('national_id') }}"
                       class="form-control @error('national_id') is-invalid @enderror">
                @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="economic_code">کد اقتصادی</label>
                <input type="text" name="economic_code" id="economic_code" value="{{ old('economic_code') }}"
                       class="form-control @error('economic_code') is-invalid @enderror">
                @error('economic_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="phone">تلفن</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                       class="form-control @error('phone') is-invalid @enderror">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-9">
                <label class="form-label" for="address">آدرس</label>
                <input type="text" name="address" id="address" value="{{ old('address') }}"
                       class="form-control @error('address') is-invalid @enderror">
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="description">توضیحات</label>
                <textarea name="description" id="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            @if(isset($representatives) && $representatives->isNotEmpty())
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <label class="form-label mb-0">نمایندگان مرتبط با این صاحب کالا (اختیاری)</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               style="max-width: 280px;"
                               data-owner-checkbox-search="#owner-create-reps-list"
                               placeholder="جستجوی نام نماینده…">
                    </div>
                    <div class="row g-2" id="owner-create-reps-list">
                        @foreach($representatives as $rep)
                            <div class="col-md-4" data-owner-checkbox-item>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="representative_ids[]"
                                           value="{{ $rep->id }}" id="rep_{{ $rep->id }}"
                                           @checked(in_array($rep->id, $defaultReps))>
                                    <label class="form-check-label" for="rep_{{ $rep->id }}">{{ $rep->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">ذخیره</button>
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection
