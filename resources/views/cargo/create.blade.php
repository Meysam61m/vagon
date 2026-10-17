@extends('layouts.app')
@section('title', 'ثبت درخواست بارگیری')

@section('content')
    <h4 class="mb-3">ثبت درخواست بارگیری جدید</h4>

    @php
        $oldWagons = old('wagon_numbers', ['']);
    @endphp

    <form method="POST" action="{{ route('cargo.store') }}" class="card p-4">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="owner_id_input">صاحب کالا</label>
                <select name="owner_id" id="owner_id_input" class="form-select @error('owner_id') is-invalid @enderror" required>
                    <option value="">انتخاب کنید…</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) old('owner_id') === (string) $owner->id)>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
                @error('owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="representative_id">نماینده</label>
                <select name="representative_id" id="representative_id"
                        class="form-select @error('representative_id') is-invalid @enderror" required>
                    <option value="">ابتدا صاحب کالا را انتخاب کنید…</option>
                    @foreach($representatives as $representative)
                        <option value="{{ $representative->id }}"
                                data-owners="{{ $representative->owners->pluck('id')->implode(',') }}"
                                @selected((string) old('representative_id') === (string) $representative->id)>
                            {{ $representative->name }}
                        </option>
                    @endforeach
                </select>
                @error('representative_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="request_date_shamsi">تاریخ درخواست (شمسی)</label>
                <input type="text" name="request_date_shamsi" id="request_date_shamsi"
                       value="{{ old('request_date_shamsi', $requestDateShamsi) }}"
                       class="form-control jalali-datepicker @error('request_date_shamsi') is-invalid @enderror"
                       autocomplete="off" placeholder="۱۴۰۴/۰۷/۲۵" required>
                @error('request_date_shamsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">شماره واگن‌ها</label>
                <div id="wagon-list">
                    @foreach($oldWagons as $wagonNumber)
                        <div class="input-group mb-2">
                            <input type="text" name="wagon_numbers[]" class="form-control"
                                   value="{{ $wagonNumber }}" placeholder="مثلاً ۵۶۳۱۲۴" required>
                            <button type="button" class="btn btn-outline-danger remove-wagon">حذف</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-wagon" class="btn btn-sm btn-outline-secondary">افزودن واگن</button>
                @error('wagon_numbers')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('wagon_numbers.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">توضیحات</label>
                <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">ثبت درخواست</button>
            <a href="{{ route('cargo.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wagonList = document.getElementById('wagon-list');

            document.getElementById('add-wagon').addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'input-group mb-2';
                row.innerHTML = '<input type="text" name="wagon_numbers[]" class="form-control" placeholder="مثلاً ۵۶۳۱۲۴" required>' +
                    '<button type="button" class="btn btn-outline-danger remove-wagon">حذف</button>';
                wagonList.appendChild(row);
            });

            wagonList.addEventListener('click', function (event) {
                if (!event.target.classList.contains('remove-wagon')) {
                    return;
                }

                if (wagonList.querySelectorAll('.input-group').length > 1) {
                    event.target.closest('.input-group').remove();
                }
            });

            // فیلتر نمایندگان بر اساس صاحب کالای انتخاب‌شده
            const ownerSelect = document.getElementById('owner_id_input');
            const representativeSelect = document.getElementById('representative_id');
            const allOptions = Array.from(representativeSelect.querySelectorAll('option[data-owners]'));
            const selected = representativeSelect.value;

            function filterRepresentatives() {
                const ownerId = ownerSelect.value;
                const current = representativeSelect.value || selected;

                representativeSelect.innerHTML = '<option value="">انتخاب کنید…</option>';

                allOptions.forEach(function (option) {
                    const owners = (option.dataset.owners || '').split(',').filter(Boolean);

                    if (!ownerId || owners.includes(ownerId)) {
                        const clone = option.cloneNode(true);
                        if (clone.value === current) {
                            clone.selected = true;
                        }
                        representativeSelect.appendChild(clone);
                    }
                });
            }

            ownerSelect.addEventListener('change', filterRepresentatives);

            if (ownerSelect.value) {
                filterRepresentatives();
            }
        });
    </script>
@endpush
