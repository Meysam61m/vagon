@extends('layouts.app')
@section('title', 'ثبت درخواست بارگیری')

@section('content')
    <h4 class="mb-3">ثبت درخواست بارگیری جدید</h4>

    @php
        $oldWagons = old('wagons');

        if (! is_array($oldWagons) || $oldWagons === []) {
            $oldWagons = [0 => ['number' => '', 'description' => '']];
        }

        // کلیدها دست نمی‌خورند تا پیام خطای هر ردیف به همان ردیف بچسبد
        $nextWagonIndex = count($oldWagons) > 0 ? max(array_map('intval', array_keys($oldWagons))) + 1 : 1;
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
                <label class="form-label">واگن‌ها و شرح کالا <span class="text-danger">*</span></label>
                <div class="text-muted small mb-2">
                    شرح کالا برای هر واگن الزامی است. اگر چند واگن همان کالا را دارند، شرح کالای ردیف بعدی به‌صورت خودکار
                    از ردیف قبل پر می‌شود و در صورت نیاز می‌توانید تغییرش دهید.
                </div>
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
                                       data-wagon-number
                                       class="form-control @error('wagons.'.$index.'.number') is-invalid @enderror"
                                       value="{{ $wagon['number'] ?? '' }}" placeholder="مثلاً ۵۶۳۱۲۴"
                                       autocomplete="off">
                                @error('wagons.'.$index.'.number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="wagons[{{ $index }}][description]"
                                       data-wagon-description
                                       class="form-control @error('wagons.'.$index.'.description') is-invalid @enderror"
                                       value="{{ $wagon['description'] ?? '' }}" placeholder="مثلاً گندم"
                                       autocomplete="off">
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
            let wagonIndex = {{ $nextWagonIndex }};

            function rows() {
                return Array.from(wagonList.querySelectorAll('.wagon-row'));
            }

            /**
             * شرح کالای نزدیک‌ترین ردیف قبل که مقدار دارد.
             */
            function previousDescription(row) {
                let cursor = row.previousElementSibling;

                while (cursor) {
                    const input = cursor.querySelector('[data-wagon-description]');

                    if (input && input.value.trim() !== '') {
                        return input.value;
                    }

                    cursor = cursor.previousElementSibling;
                }

                return '';
            }

            /**
             * شرح کالا فقط برای ردیفی اجباری است که شماره واگن دارد؛
             * ردیف‌های خالی اضافی جلوی ارسال فرم را نمی‌گیرند.
             * پیام اعتبارسنجی هم فارسی و اختصاصی همین فیلد است.
             */
            function refreshRow(row) {
                const number = row.querySelector('[data-wagon-number]');
                const description = row.querySelector('[data-wagon-description]');

                if (! number || ! description) {
                    return;
                }

                const needed = number.value.trim() !== '';
                const empty = description.value.trim() === '';

                description.required = needed;
                description.placeholder = needed ? 'مثلاً گندم (الزامی)' : 'مثلاً گندم';
                description.setCustomValidity(
                    needed && empty ? 'شرح کالا برای این واگن الزامی است.' : ''
                );
            }

            function buildRow(index) {
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 wagon-row';

                const numberColumn = document.createElement('div');
                numberColumn.className = 'col-md-4';

                const numberInput = document.createElement('input');
                numberInput.type = 'text';
                numberInput.name = 'wagons[' + index + '][number]';
                numberInput.className = 'form-control';
                numberInput.placeholder = 'مثلاً ۵۶۳۱۲۴';
                numberInput.autocomplete = 'off';
                numberInput.setAttribute('data-wagon-number', '');
                numberColumn.appendChild(numberInput);

                const descriptionColumn = document.createElement('div');
                descriptionColumn.className = 'col-md-6';

                const descriptionInput = document.createElement('input');
                descriptionInput.type = 'text';
                descriptionInput.name = 'wagons[' + index + '][description]';
                descriptionInput.className = 'form-control';
                descriptionInput.placeholder = 'مثلاً گندم';
                descriptionInput.autocomplete = 'off';
                descriptionInput.setAttribute('data-wagon-description', '');
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

                return { row: row, number: numberInput, description: descriptionInput };
            }

            document.getElementById('add-wagon').addEventListener('click', function () {
                const existing = rows();
                const lastRow = existing.length > 0 ? existing[existing.length - 1] : null;
                const lastDescription = lastRow ? lastRow.querySelector('[data-wagon-description]') : null;

                // پیشنهاد شرح کالا: اول از ردیف آخر، بعد از نزدیک‌ترین ردیف قبلی که مقدار دارد
                const suggested = lastDescription && lastDescription.value.trim() !== ''
                    ? lastDescription.value
                    : (lastRow ? previousDescription(lastRow) : '');

                const created = buildRow(wagonIndex++);

                if (suggested !== '') {
                    created.description.value = suggested;
                    created.description.dataset.autoFilled = '1';
                }

                wagonList.appendChild(created.row);
                refreshRow(created.row);
                created.number.focus();
            });

            wagonList.addEventListener('click', function (event) {
                if (!event.target.classList.contains('remove-wagon')) {
                    return;
                }

                if (rows().length > 1) {
                    event.target.closest('.wagon-row').remove();
                }
            });

            // با پر شدن شماره واگن، شرح کالای همان ردیف اجباری می‌شود
            wagonList.addEventListener('input', function (event) {
                const row = event.target.closest('.wagon-row');

                if (!row) {
                    return;
                }

                if (event.target.matches('[data-wagon-number]')) {
                    refreshRow(row);
                    return;
                }

                if (!event.target.matches('[data-wagon-description]')) {
                    return;
                }

                // این ردیف را دستی ویرایش کرده‌اند؛ دیگر خودکار بازنویسی نشود
                delete event.target.dataset.autoFilled;
                refreshRow(row);

                let next = row.nextElementSibling;

                while (next) {
                    const nextInput = next.querySelector('[data-wagon-description]');

                    if (!nextInput) {
                        break;
                    }

                    // اولین ردیفی که کاربر خودش شرحش را نوشته، مرز گروه است
                    if (!nextInput.dataset.autoFilled && nextInput.value.trim() !== '') {
                        break;
                    }

                    if (nextInput.value.trim() === '' || nextInput.dataset.autoFilled) {
                        nextInput.value = event.target.value;
                        nextInput.dataset.autoFilled = '1';
                        refreshRow(next);
                    }

                    next = next.nextElementSibling;
                }
            });

            // وضعیت اولیه ردیف‌ها (بعد از خطای اعتبارسنجی یا مقدارهای قبلی)
            rows().forEach(refreshRow);
        });
    </script>
@endpush
