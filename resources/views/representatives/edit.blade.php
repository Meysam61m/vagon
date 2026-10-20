@extends('layouts.app')
@section('title', 'ویرایش نماینده')

@section('content')
    <h4 class="mb-3">ویرایش: {{ $representative->name }}</h4>

    @php
        $selectedOwners = old('owner_ids', $representative->owners->pluck('id')->all());
    @endphp

    <form method="POST" action="{{ route('representatives.update', $representative) }}" class="card p-4">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="name">نام</label>
                <input type="text" name="name" id="name" value="{{ old('name', $representative->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="national_id">کد ملی</label>
                <input type="text" name="national_id" id="national_id"
                       value="{{ old('national_id', $representative->national_id) }}"
                       class="form-control @error('national_id') is-invalid @enderror">
                @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="phone">تلفن</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $representative->phone) }}"
                       class="form-control @error('phone') is-invalid @enderror">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="form-label mb-0">صاحبان کالای مرتبط</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           style="max-width: 280px;"
                           data-owner-checkbox-search="#rep-edit-owners-list"
                           placeholder="جستجوی نام صاحب کالا…">
                </div>
                <div class="row g-2" id="rep-edit-owners-list">
                    @foreach($owners as $owner)
                        <div class="col-md-4" data-owner-checkbox-item>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="owner_ids[]"
                                       value="{{ $owner->id }}" id="owner_{{ $owner->id }}"
                                       @checked(in_array($owner->id, $selectedOwners))>
                                <label class="form-check-label" for="owner_{{ $owner->id }}">{{ $owner->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('owner_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">بروزرسانی</button>
            <a href="{{ route('representatives.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection
