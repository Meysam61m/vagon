@extends('layouts.app')
@section('title', 'ثبت درخواست بارگیری')

@section('content')
    <h4 class="mb-3">ثبت درخواست بارگیری جدید</h4>

    @php
        $oldWagons = old('wagons');

        if (! is_array($oldWagons) || $oldWagons === []) {
            $oldWagons = [['number' => '', 'description' => '']];
        }

        $oldWagons = array_values($oldWagons);
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
                <label class="form-label">واگن‌ها و شرح کالا</label>
                <div class="row g-2 mb-1 text-muted small d-none d-md-flex">
                    <div class="col-md-4">شماره واگن</div>
                    <div class="col-md-6">شرح کالا</div>
                    <div class="col-md-2"></div>
                </div>
                <div id="wagon-list">
                    @foreach($oldWagons as $index => $wagon)
                        <div class="row g-2 mb-2 wagon-row">
                            <div class="col-md-4">
                                <input type="text" name="wagons[{{ $index }}][number]"
                                       class="form-control @error('wagons.'.$index.'.number') is-invalid @enderror"
                                       value="{{ $wagon['number'] ?? '' }}" placeholder="مثلاً ۵۶۳۱۲۴">
                                @error('wagons.'.$index.'.number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="wagons[{{ $index }}][description]"
                                       class="form-control @error('wagons.'.$index.'.description') is-invalid @enderror"
                                       value="{{ $wagon['description'] ?? '' }}" placeholder="مثلاً گندم">
                                @error('wagons.'.$index.'.description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-wagon">حذف</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-wagon" class="btn btn-sm btn-outline-secondary">افزودن واگن</button>
                @error('wagons')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('wagons.*.number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('wagons.*.description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
            let wagonIndex = {{ count($oldWagons) }};

            function createWagonRow(index) {
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 wagon-row';

                const numberColumn = document.createElement('div');
                numberColumn.className = 'col-md-4';

                const numberInput = document.createElement('input');
                numberInput.type = 'text';
                numberInput.name = 'wagons[' + index + '][number]';
                numberInput.className = 'form-control';
                numberInput.placeholder = 'مثلاً ۵۶۳۱۲۴';
                numberColumn.appendChild(numberInput);

                const descriptionColumn = document.createElement('div');
                descriptionColumn.className = 'col-md-6';

                const descriptionInput = document.createElement('input');
                descriptionInput.type = 'text';
                descriptionInput.name = 'wagons[' + index + '][description]';
                descriptionInput.className = 'form-control';
                descriptionInput.placeholder = 'مثلاً گندم';
                descriptionColumn.appendChild(descriptionInput);

                const actionsColumn = document.createElement('div');
                actionsColumn.className = 'col-md-2';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger w-100 remove-wagon';
                removeButton.textContent = 'حذف';
                actionsColumn.appendChild(removeButton);

                row.appendChild(numberColumn);
                row.appendChild(descriptionColumn);
                row.appendChild(actionsColumn);

                return { row: row, input: numberInput };
            }

            document.getElementById('add-wagon').addEventListener('click', function () {
                const created = createWagonRow(wagonIndex++);
                wagonList.appendChild(created.row);
                created.input.focus();
            });

            wagonList.addEventListener('click', function (event) {
                if (!event.target.classList.contains('remove-wagon')) {
                    return;
                }

                if (wagonList.querySelectorAll('.wagon-row').length > 1) {
                    event.target.closest('.wagon-row').remove();
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
