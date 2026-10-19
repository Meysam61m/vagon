@extends('layouts.app')
@section('title', 'ویرایش واگن ' . $wagon->wagon_number)

@section('content')
    @php
        $billing = $wagon->billingItem?->billing;
        $currentOwnerId = $wagon->effectiveOwnerId();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">
            ویرایش واگن: <span class="text-primary">{{ $wagon->wagon_number }}</span>
            <span class="fs-6 text-muted fw-normal">(اصلاح شماره واگن / تغییر صاحب کالا)</span>
        </h4>
        <div class="d-flex gap-2">
            @if($billing)
                <a href="{{ route('billing.show', $billing) }}" class="btn btn-outline-secondary">
                    بازگشت به صورتحساب {{ $billing->billing_number }}
                </a>
            @elseif($wagon->cargoRequest)
                <a href="{{ route('cargo.show', $wagon->cargoRequest) }}" class="btn btn-outline-secondary">
                    بازگشت به درخواست {{ $wagon->cargoRequest->request_number }}
                </a>
            @else
                <a href="{{ route(auth()->user()->isAdmin() ? 'dashboard' : 'cargo.index') }}" class="btn btn-outline-secondary">بازگشت</a>
            @endif
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h6 class="mb-3">مشخصات فعلی واگن</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <strong>شماره واگن فعلی:</strong>
                <span class="fw-bold">{{ $wagon->wagon_number }}</span>
                @if($wagon->isNumberEdited())
                    <span class="text-muted small">(شماره قبلی: {{ $wagon->previous_wagon_number }})</span>
                @endif
            </div>
            <div class="col-md-3">
                <strong>شرح کالا:</strong> {{ $wagon->cargo_description ?: '—' }}
            </div>
            <div class="col-md-3">
                <strong>صاحب کالای فعلی:</strong>
                <span class="badge bg-primary">{{ $wagon->ownerName() }}</span>
                @if($wagon->previousOwner && (int) $wagon->previous_owner_id !== (int) $currentOwnerId)
                    <span class="text-muted small">(قبلی: {{ $wagon->previousOwner->name }})</span>
                @endif
            </div>
            <div class="col-md-3">
                <strong>شماره درخواست بارگیری:</strong>
                @if($wagon->cargoRequest)
                    <a href="{{ route('cargo.show', $wagon->cargoRequest) }}">{{ $wagon->cargoRequest->request_number }}</a>
                @else
                    —
                @endif
            </div>
            <div class="col-md-3">
                <strong>وزن بارگیری‌شده:</strong>
                {{ $wagon->loaded_weight ? $wagon->loaded_weight . ' تن' : '—' }}
            </div>
            <div class="col-md-3">
                <strong>تعرفه:</strong> {{ $wagon->tariff?->title ?? '—' }}
            </div>
            <div class="col-md-3">
                <strong>وضعیت صورتحساب:</strong>
                @if($billing)
                    <a href="{{ route('billing.show', $billing) }}" class="badge bg-success text-decoration-none">
                        {{ $billing->billing_number }}
                    </a>
                @elseif($wagon->is_settled)
                    <span class="badge bg-success">تسویه شده</span>
                @else
                    <span class="badge bg-warning text-dark">در انتظار تسویه</span>
                @endif
            </div>
            <div class="col-md-3">
                <strong>تعداد دفعات ویرایش:</strong>
                <span class="badge {{ $wagon->isOwnerEdited() ? 'bg-warning text-dark' : 'bg-secondary' }}">
                    {{ $wagon->owner_change_count }} بار
                </span>
            </div>
        </div>

        @if($wagon->isOwnerEdited() && $wagon->owner_change_note)
            <div class="alert alert-warning mt-3 mb-0">
                <div class="fw-bold mb-1">آخرین علت و توضیح ثبت‌شده برای ویرایش این واگن ({{ $wagon->editTypeLabel() }}):</div>
                <div>{{ $wagon->owner_change_note }}</div>
                <div class="small text-muted mt-1">
                    زمان ویرایش: @jdatetime($wagon->owner_changed_at)
                    @if($wagon->ownerChangedBy)
                        | کاربر ویرایش‌کننده: {{ $wagon->ownerChangedBy->name }}
                    @endif
                </div>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('wagons.update-owner') ? route('wagons.update-owner', $wagon) : url('/wagons/'.$wagon->id.'/owner') }}" class="card p-4 mb-4">
        @csrf
        @if($billing)
            <input type="hidden" name="billing_id" value="{{ $billing->id }}">
        @endif

        <h6 class="mb-2">ثبت اصلاح شماره واگن یا تغییر صاحب کالا</h6>
        <p class="text-muted small mb-3">
            اگر پس از صدور صورتحساب متوجه شده‌اید که <strong>شماره واگن اشتباه تایپ شده است</strong> یا <strong>صاحب کالای واگن تغییر کرده است</strong>،
            مقدار صحیح را در فرم زیر وارد کرده و علت ویرایش را بنویسید. این ویرایش با ذکر علت، نام کاربر و زمان دقیق در <strong>داشبورد مدیریتی</strong> ثبت و نمایش داده می‌شود.
        </p>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="wagon_number">
                    شماره واگن (صحیح) <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="wagon_number"
                       id="wagon_number"
                       maxlength="50"
                       value="{{ old('wagon_number', $wagon->wagon_number) }}"
                       class="form-control @error('wagon_number') is-invalid @enderror"
                       placeholder="شماره صحیح واگن را وارد کنید"
                       required>
                @error('wagon_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    شماره فعلی: <strong>{{ $wagon->wagon_number }}</strong> — در صورت اشتباه تایپی، شماره صحیح را وارد کنید.
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="owner_id">
                    صاحب کالا
                    @if($wagon->is_settled || $billing)
                        <span class="text-danger">*</span>
                    @else
                        <span class="badge bg-warning-subtle text-dark border small fw-normal">اختیاری قبل از فاکتور</span>
                    @endif
                </label>
                <select name="owner_id" id="owner_id"
                        class="form-select @error('owner_id') is-invalid @enderror"
                        {{ ($wagon->is_settled || $billing) ? 'required' : '' }}>
                    @if(! $wagon->is_settled && ! $billing)
                        <option value="" @selected(old('owner_id', $currentOwnerId) === null || old('owner_id', $currentOwnerId) === '')>
                            — ناشناس / بدون صاحب کالا (تعیین هنگام صدور صورتحساب) —
                        </option>
                    @endif
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}"
                            @selected((string) old('owner_id', $currentOwnerId) === (string) $owner->id)>
                            {{ $owner->name }}
                            {{ $currentOwnerId && (int) $owner->id === (int) $currentOwnerId ? '(صاحب فعلی واگن)' : '' }}
                            {{ ! $owner->is_active ? '(غیرفعال)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('owner_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    اگر فقط شماره واگن را اصلاح می‌کنید، صاحب کالا را روی همان وضعیت فعلی بگذارید.
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="representative_id">نماینده صاحب کالا (اختیاری)</label>
                <select name="representative_id" id="representative_id"
                        class="form-select @error('representative_id') is-invalid @enderror">
                    <option value="">انتخاب خودکار / بدون تغییر…</option>
                    @foreach($representatives as $rep)
                        <option value="{{ $rep->id }}"
                                data-owners="{{ $rep->owners->pluck('id')->join(',') }}"
                                @selected((string) old('representative_id') === (string) $rep->id)>
                            {{ $rep->name }}
                        </option>
                    @endforeach
                </select>
                @error('representative_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label class="form-label" for="owner_change_note">
                    علت و توضیحات ویرایش واگن <span class="text-danger">*</span>
                </label>
                <textarea name="owner_change_note"
                          id="owner_change_note"
                          rows="3"
                          required
                          minlength="3"
                          class="form-control @error('owner_change_note') is-invalid @enderror"
                          placeholder="علت اصلاح شماره واگن (مثلاً اشتباه تایپی هنگام ثبت اولیه) یا علت تغییر صاحب کالا پس از صدور صورتحساب را بنویسید (الزامی)…">{{ old('owner_change_note') }}</textarea>
                @error('owner_change_note')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">ذخیره ویرایش واگن</button>
            @if($billing)
                <a href="{{ route('billing.show', $billing) }}" class="btn btn-outline-secondary">انصراف</a>
            @elseif($wagon->cargoRequest)
                <a href="{{ route('cargo.show', $wagon->cargoRequest) }}" class="btn btn-outline-secondary">انصراف</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>تاریخچه کامل ویرایش‌های این واگن</span>
            <span class="badge bg-secondary">{{ $wagon->ownerLogs->count() }} مورد</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>زمان ویرایش</th>
                    <th>نوع ویرایش</th>
                    <th>شماره واگن (قبلی ← جدید)</th>
                    <th>صاحب کالا (قبلی ← جدید)</th>
                    <th>شماره صورتحساب</th>
                    <th>کاربر ویرایش‌کننده</th>
                    <th>علت و توضیحات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($wagon->ownerLogs as $log)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-nowrap">@jdatetime($log->created_at)</td>
                        <td>
                            @if($log->change_type === 'wagon_number')
                                <span class="badge bg-info text-dark">اصلاح شماره واگن</span>
                            @elseif($log->change_type === 'both')
                                <span class="badge bg-danger">اصلاح شماره و صاحب کالا</span>
                            @else
                                <span class="badge bg-warning text-dark">تغییر صاحب کالا</span>
                            @endif
                        </td>
                        <td>
                            @if($log->isWagonNumberChange())
                                <span class="text-decoration-line-through text-danger">{{ $log->old_wagon_number }}</span>
                                <span class="mx-1">←</span>
                                <strong class="text-success">{{ $log->new_wagon_number }}</strong>
                            @else
                                <span class="text-muted">{{ $log->new_wagon_number ?: $wagon->wagon_number }} (بدون تغییر)</span>
                            @endif
                        </td>
                        <td>
                            @if((int) $log->old_owner_id !== (int) $log->new_owner_id)
                                <span class="text-muted">{{ $log->old_owner_name }}</span>
                                <span class="mx-1">←</span>
                                <strong class="text-primary">{{ $log->new_owner_name }}</strong>
                            @else
                                <span class="text-muted">{{ $log->new_owner_name }} (بدون تغییر)</span>
                            @endif
                        </td>
                        <td>
                            @if($log->billing)
                                <a href="{{ route('billing.show', $log->billing) }}">{{ $log->billing->billing_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td>{{ $log->note }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
                            هنوز هیچ ویرایشی برای این واگن ثبت نشده است.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ownerSelect = document.getElementById('owner_id');
            const repSelect = document.getElementById('representative_id');

            function filterRepresentatives() {
                const ownerId = ownerSelect.value;

                Array.from(repSelect.options).forEach(function (option) {
                    if (! option.value) {
                        return;
                    }

                    const owners = (option.dataset.owners || '').split(',').filter(Boolean);
                    const allowed = ! ownerId || owners.includes(ownerId);

                    option.hidden = ! allowed;

                    if (! allowed && option.selected) {
                        repSelect.value = '';
                    }
                });
            }

            ownerSelect.addEventListener('change', filterRepresentatives);
            filterRepresentatives();
        });
    </script>
@endpush
