@extends('layouts.app')
@section('title', 'ویرایش صاحب کالای واگن ' . $wagon->wagon_number)

@section('content')
    @php
        $billing = $wagon->billingItem?->billing;
        $currentOwnerId = $wagon->effectiveOwnerId();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">
            ویرایش صاحب کالای واگن: <span class="text-primary">{{ $wagon->wagon_number }}</span>
            @if($wagon->isOwnerEdited())
                <span class="badge bg-warning text-dark fs-6 align-middle">ویرایش‌شده ({{ $wagon->owner_change_count }} بار)</span>
            @endif
        </h4>
        <div class="d-flex gap-2">
            @if($billing)
                <a href="{{ route('billing.show', $billing) }}" class="btn btn-outline-secondary btn-sm">
                    بازگشت به صورتحساب {{ $billing->billing_number }}
                </a>
            @endif
            @if($wagon->cargoRequest)
                <a href="{{ route('cargo.show', $wagon->cargoRequest) }}" class="btn btn-outline-secondary btn-sm">
                    مشاهده درخواست {{ $wagon->cargoRequest->request_number }}
                </a>
            @endif
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h6 class="mb-3">مشخصات فعلی واگن</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <strong>شماره واگن:</strong> {{ $wagon->wagon_number }}
            </div>
            <div class="col-md-3">
                <strong>شرح کالا:</strong> {{ $wagon->cargo_description ?: '—' }}
            </div>
            <div class="col-md-3">
                <strong>صاحب کالای فعلی:</strong>
                <span class="fw-bold">{{ $wagon->ownerName() }}</span>
            </div>
            <div class="col-md-3">
                <strong>وزن بارگیری:</strong> {{ $wagon->loaded_weight ? $wagon->loaded_weight . ' تن' : '—' }}
            </div>
            <div class="col-md-3">
                <strong>درخواست بارگیری:</strong>
                @if($wagon->cargoRequest)
                    <a href="{{ route('cargo.show', $wagon->cargoRequest) }}">{{ $wagon->cargoRequest->request_number }}</a>
                @else
                    —
                @endif
            </div>
            <div class="col-md-3">
                <strong>صورتحساب:</strong>
                @if($billing)
                    <a href="{{ route('billing.show', $billing) }}">{{ $billing->billing_number }}</a>
                    <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }}">
                        {{ $billing->statusLabel() }}
                    </span>
                @else
                    <span class="text-muted">صادر نشده</span>
                @endif
            </div>
            @if($wagon->isOwnerEdited())
                <div class="col-md-3">
                    <strong>صاحب کالای قبلی:</strong> {{ $wagon->previousOwner?->name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>آخرین ویرایش:</strong>
                    @jdatetime($wagon->owner_changed_at)
                    @if($wagon->ownerChangedBy)
                        <span class="text-muted small">({{ $wagon->ownerChangedBy->name }})</span>
                    @endif
                </div>
                <div class="col-12">
                    <div class="alert alert-warning mb-0 py-2">
                        <strong>آخرین توضیح ثبت‌شده:</strong> {{ $wagon->owner_change_note }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('wagons.update-owner', $wagon) }}" class="card p-4 mb-4">
        @csrf
        @method('PUT')
        @if(request('billing_id') || $billing)
            <input type="hidden" name="billing_id" value="{{ request('billing_id', $billing?->id) }}">
        @endif

        <h6 class="mb-2">ثبت تغییر صاحب کالا</h6>
        <p class="text-muted small mb-3">
            در صورتی که پس از صدور صورتحساب، صاحب کالای این واگن تغییر کرده است، صاحب کالای جدید را انتخاب کرده و علت تغییر را به‌صورت کامل بنویسید. این تغییر در آمار واگن‌های ویرایش‌شده در داشبورد مدیر ثبت می‌شود.
        </p>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="owner_id">
                    صاحب کالای جدید <span class="text-danger">*</span>
                </label>
                <select name="owner_id" id="owner_id"
                        class="form-select @error('owner_id') is-invalid @enderror" required>
                    <option value="">انتخاب صاحب کالای جدید…</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}"
                            @selected((string) old('owner_id') === (string) $owner->id)
                            @disabled((int) $owner->id === (int) $currentOwnerId && ! old('owner_id'))>
                            {{ $owner->name }}
                            @if((int) $owner->id === (int) $currentOwnerId)
                                (صاحب کالای فعلی)
                            @elseif(! $owner->is_active)
                                (غیرفعال)
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('owner_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="representative_id">نماینده صاحب کالای جدید (اختیاری)</label>
                <select name="representative_id" id="representative_id"
                        class="form-select @error('representative_id') is-invalid @enderror">
                    <option value="">انتخاب خودکار / بدون تغییر…</option>
                    @foreach($representatives as $rep)
                        <option value="{{ $rep->id }}"
                                data-owners="{{ $rep->owners->pluck('id')->implode(',') }}"
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
                    توضیحات تغییر صاحب کالا <span class="text-danger">*</span>
                </label>
                <textarea name="owner_change_note" id="owner_change_note" rows="3" required minlength="3"
                          class="form-control @error('owner_change_note') is-invalid @enderror"
                          placeholder="علت تغییر صاحب کالای واگن پس از صدور صورتحساب را وارد کنید (الزامی)…">{{ old('owner_change_note') }}</textarea>
                @error('owner_change_note')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">ذخیره تغییر صاحب کالا</button>
            @if($billing)
                <a href="{{ route('billing.show', $billing) }}" class="btn btn-secondary">انصراف</a>
            @elseif($wagon->cargoRequest)
                <a href="{{ route('cargo.show', $wagon->cargoRequest) }}" class="btn btn-secondary">انصراف</a>
            @else
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">انصراف</a>
            @endif
        </div>
    </form>

    @if($wagon->ownerLogs->isNotEmpty())
        <div class="card">
            <div class="card-header">
                تاریخچه ویرایش‌های صاحب کالای این واگن ({{ $wagon->ownerLogs->count() }} مورد)
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 bg-white">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>زمان ویرایش</th>
                        <th>صاحب کالای قبلی</th>
                        <th>صاحب کالای جدید</th>
                        <th>شماره صورتحساب</th>
                        <th>کاربر ویرایش‌کننده</th>
                        <th>توضیحات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($wagon->ownerLogs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-nowrap">@jdatetime($log->created_at)</td>
                            <td>{{ $log->old_owner_name }}</td>
                            <td class="fw-bold">{{ $log->new_owner_name }}</td>
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
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ownerSelect = document.getElementById('owner_id');
            const repSelect = document.getElementById('representative_id');

            if (! ownerSelect || ! repSelect) {
                return;
            }

            function filterRepresentatives() {
                const ownerId = ownerSelect.value;

                Array.from(repSelect.options).forEach(function (opt) {
                    if (! opt.value) {
                        return;
                    }

                    const owners = (opt.getAttribute('data-owners') || '').split(',');
                    const allowed = ! ownerId || owners.includes(ownerId);

                    opt.hidden = ! allowed;
                    if (! allowed && opt.selected) {
                        opt.selected = false;
                    }
                });
            }

            ownerSelect.addEventListener('change', filterRepresentatives);
            filterRepresentatives();
        });
    </script>
@endpush
