@extends('layouts.app')
@section('title', 'جزئیات درخواست بارگیری')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">درخواست بارگیری: {{ $cargo->request_number }}</h4>
        <span class="badge bg-secondary fs-6">{{ $cargo->statusLabel() }}</span>
    </div>

    <div class="card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3"><strong>صاحب کالا:</strong> {{ $cargo->owner?->name ?? '—' }}</div>
            <div class="col-md-3"><strong>نماینده:</strong> {{ $cargo->representative?->name ?? '—' }}</div>
            <div class="col-md-3"><strong>تاریخ درخواست:</strong> {{ $cargo->request_date_shamsi }}</div>
            <div class="col-md-3"><strong>اپراتور ثبت‌کننده:</strong> {{ $cargo->operator?->name ?? '—' }}</div>
        </div>
        @if($cargo->notes)
            <div class="mt-3"><strong>توضیحات:</strong> {{ $cargo->notes }}</div>
        @endif
    </div>

    @if($cargo->status !== 'billed')
        <form method="POST" action="{{ route('cargo.load', $cargo) }}" class="card p-4 mb-4">
            @csrf
            <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-1">ثبت عملیات بارگیری</h6>
                    <div class="text-muted small">
                        برای هر واگن شرح کالا، تعرفه و وزن بارگیری‌شده را وارد کنید. وزن خالی یعنی آن واگن هنوز بارگیری نشده است.
                        برای واگنی که تازه بارگیری می‌شود، شرح کالا الزامی است و با ورود شماره واگن‌های هم‌کالا، شرح ردیف بعدی پیشنهاد می‌شود.
                    </div>
                </div>
                <div style="min-width: 200px;">
                    <label class="form-label" for="loading_date_shamsi">تاریخ بارگیری (شمسی)</label>
                    <input type="text" name="loading_date_shamsi" id="loading_date_shamsi"
                           value="{{ old('loading_date_shamsi', $loadingDateShamsi) }}"
                           class="form-control jalali-datepicker" autocomplete="off" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره واگن</th>
                        <th style="min-width: 190px;">شرح کالا <span class="text-danger">*</span></th>
                        <th>صاحب کالا</th>
                        <th style="min-width: 240px;">تعرفه</th>
                        <th style="min-width: 130px;">وزن (تن)</th>
                        <th>وضعیت</th>
                        <th>صورتحساب</th>
                        <th>ویرایش واگن</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cargo->wagons as $wagon)
                        @php
                            $oldWeight = old('loading.'.$wagon->id.'.weight', $wagon->loaded_weight);
                            $oldTariff = old('loading.'.$wagon->id.'.tariff_id', $wagon->tariff_id);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $wagon->wagon_number }}</div>
                                @if($wagon->isNumberEdited())
                                    <span class="badge bg-info text-dark mt-1">شماره اصلاح‌شده</span>
                                    <div class="small text-muted">قبلی: <span class="text-decoration-line-through">{{ $wagon->previous_wagon_number }}</span></div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->cargo_description ?: '—' }}
                                @else
                                    <input type="text"
                                           name="loading[{{ $wagon->id }}][description]"
                                           value="{{ old('loading.'.$wagon->id.'.description', $wagon->cargo_description) }}"
                                           class="form-control form-control-sm @error('loading.'.$wagon->id.'.description') is-invalid @enderror"
                                           data-loading-description
                                           placeholder="مثلاً گندم">
                                    @error('loading.'.$wagon->id.'.description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </td>
                            <td>
                                <div>{{ $wagon->ownerName() }}</div>
                                @if($wagon->isOwnerChanged())
                                    <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                                    <div class="small text-muted">قبلی: {{ $wagon->previousOwner?->name ?? '—' }}</div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->tariff?->title ?? '—' }}
                                @else
                                    <select name="loading[{{ $wagon->id }}][tariff_id]" class="form-select form-select-sm">
                                        <option value="">انتخاب تعرفه…</option>
                                        @foreach($tariffs as $tariff)
                                            <option value="{{ $tariff->id }}" @selected((string) $oldTariff === (string) $tariff->id)>
                                                {{ $tariff->title }}{{ $tariff->is_active ? '' : ' (غیرفعال)' }}
                                                — {{ $tariff->capacity }} تن
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    {{ $wagon->loaded_weight }}
                                @else
                                    <input type="number" step="0.01" min="0.01" max="200"
                                           name="loading[{{ $wagon->id }}][weight]"
                                           value="{{ $oldWeight }}"
                                           class="form-control form-control-sm @error('loading.'.$wagon->id.'.weight') is-invalid @enderror">
                                    @error('loading.'.$wagon->id.'.weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </td>
                            <td>
                                @if($wagon->is_settled)
                                    <span class="badge bg-success">تسویه شده</span>
                                @elseif($wagon->isLoaded())
                                    <span class="badge bg-info text-dark">بارگیری شده</span>
                                @else
                                    <span class="badge bg-warning text-dark">در انتظار بارگیری</span>
                                @endif
                            </td>
                            <td>
                                @if($wagon->billingItem?->billing)
                                    <a href="{{ route('billing.show', $wagon->billingItem->billing) }}">
                                        {{ $wagon->billingItem->billing->billing_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ \Illuminate\Support\Facades\Route::has('wagons.edit-owner') ? route('wagons.edit-owner', $wagon) : url('/wagons/'.$wagon->id.'/edit-owner') }}" class="btn btn-sm btn-outline-primary">
                                    ویرایش واگن
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary" type="submit">ذخیره بارگیری</button>
        </form>
    @else
        <div class="card p-4 mb-4">
            <h6 class="mb-3">واگن‌های این درخواست (صورتحساب‌شده)</h6>
            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>شماره واگن</th>
                        <th>شرح کالا</th>
                        <th>صاحب کالا</th>
                        <th>تعرفه</th>
                        <th>وزن (تن)</th>
                        <th>وضعیت</th>
                        <th>صورتحساب</th>
                        <th>ویرایش واگن</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cargo->wagons as $wagon)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $wagon->wagon_number }}</div>
                                @if($wagon->isNumberEdited())
                                    <span class="badge bg-info text-dark mt-1">شماره اصلاح‌شده</span>
                                    <div class="small text-muted">قبلی: <span class="text-decoration-line-through">{{ $wagon->previous_wagon_number }}</span></div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>{{ $wagon->cargo_description ?: '—' }}</td>
                            <td>
                                <div>{{ $wagon->ownerName() }}</div>
                                @if($wagon->isOwnerChanged())
                                    <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                                    <div class="small text-muted">قبلی: {{ $wagon->previousOwner?->name ?? '—' }}</div>
                                    <div class="small text-muted">علت: {{ $wagon->owner_change_note }}</div>
                                @endif
                            </td>
                            <td>{{ $wagon->tariff?->title ?? '—' }}</td>
                            <td>{{ $wagon->loaded_weight ?? '—' }}</td>
                            <td><span class="badge bg-success">تسویه شده</span></td>
                            <td>
                                @if($wagon->billingItem?->billing)
                                    <a href="{{ route('billing.show', $wagon->billingItem->billing) }}">
                                        {{ $wagon->billingItem->billing->billing_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ \Illuminate\Support\Facades\Route::has('wagons.edit-owner') ? route('wagons.edit-owner', $wagon) : url('/wagons/'.$wagon->id.'/edit-owner') }}" class="btn btn-sm btn-outline-primary">
                                    ویرایش واگن
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <a href="{{ route('cargo.index') }}" class="btn btn-secondary">بازگشت</a>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // شرح کالا فقط برای واگنی که هنوز بارگیری نشده اجباری می‌شود
            document.querySelectorAll('input[data-loading-description]').forEach(function (input) {
                const row = input.closest('tr');
                const weight = row ? row.querySelector('input[name^="loading["][name$="[weight]"]') : null;

                if (! weight) {
                    return;
                }

                function refresh() {
                    const mustFill = weight.value.trim() !== '' && input.value.trim() === '';

                    input.required = mustFill;
                    input.setCustomValidity(mustFill ? 'شرح کالا برای این واگن الزامی است.' : '');
                }

                weight.addEventListener('input', refresh);
                input.addEventListener('input', refresh);
                refresh();
            });

            // با نوشتن شرح یک واگن، ردیف‌های بعدیِ خالی همان شرح را پیشنهاد می‌گیرند
            document.querySelectorAll('input[data-loading-description]').forEach(function (input) {
                input.addEventListener('input', function () {
                    let nextRow = input.closest('tr') ? input.closest('tr').nextElementSibling : null;

                    while (nextRow) {
                        const nextInput = nextRow.querySelector('input[data-loading-description]');

                        if (! nextInput) {
                            break;
                        }

                        if (nextInput.value.trim() === '') {
                            nextInput.value = input.value;
                            nextInput.dispatchEvent(new Event('input', { bubbles: true }));
                        } else {
                            break;
                        }

                        nextRow = nextRow.nextElementSibling;
                    }
                });
            });
        });
    </script>
@endpush
