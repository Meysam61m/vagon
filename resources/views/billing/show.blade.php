@extends('layouts.app')
@section('title', 'جزئیات صورتحساب')

@section('content')
    @php
        $defaultReceiptDate = $billing->receipt_date_shamsi
            ?: ($todayShamsi ?? \App\Support\JalaliDate::today());
        $settleUrl = \Illuminate\Support\Facades\Route::has('billing.settle')
            ? route('billing.settle', $billing)
            : url('/billing/'.$billing->id.'/settle');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="لوگو" class="bg-white border rounded p-1 shadow-sm" style="height: 46px; width: auto;">
            <div>
                <h4 class="mb-0">
                    صورتحساب: {{ $billing->billing_number }}
                    <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : ($billing->isSettled() ? 'bg-success' : 'bg-warning text-dark') }} fs-6 align-middle">
                        {{ $billing->statusLabel() }}
                    </span>
                </h4>
                <div class="text-muted small">شرکت انبارهای عمومی و خدمات گمرکی ایران — مجتمع شهید مطهری</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(! $billing->isCancelled())
                @if($billing->isSettled())
                    <a href="{{ route('billing.print', $billing) }}" target="_blank" rel="noopener" class="btn btn-primary fw-semibold">
                        چاپ برگه ۴ بخشی تسویه حساب
                    </a>
                @else
                    <a href="#settlement-receipt-form"
                       class="btn btn-warning fw-semibold"
                       onclick="setTimeout(function(){ var el = document.getElementById('receipt_number'); if(el) el.focus(); }, 100);">
                        ثبت فیش واریزی جهت چاپ برگه ۴ بخشی
                    </a>
                @endif
            @endif
            <a href="{{ route('cargo.create') }}" class="btn btn-outline-primary">
                + ثبت درخواست جدید
            </a>
            <a href="{{ route('billing.create') }}" class="btn btn-outline-success">
                + صدور صورتحساب بعدی
            </a>
            @if(auth()->user()->isAdmin() && ! $billing->isCancelled())
                <form action="{{ route('billing.cancel', $billing) }}" method="POST"
                      onsubmit="return confirm('صورتحساب لغو شود؟ واگن‌ها آزاد می‌شوند و امکان صدور مجدد فراهم می‌شود.');">
                    @csrf
                    <button class="btn btn-outline-danger" type="submit">لغو صورتحساب</button>
                </form>
            @endif
        </div>
    </div>

    @if(! $billing->isCancelled())
        <datalist id="common-banks-list">
            <option value="بانک ملی ایران"></option>
            <option value="بانک ملت"></option>
            <option value="بانک صادرات ایران"></option>
            <option value="بانک تجارت"></option>
            <option value="بانک سپه"></option>
            <option value="بانک رفاه کارگران"></option>
            <option value="کارتخوان (POS)"></option>
            <option value="پایا / ساتنا"></option>
        </datalist>

        @if(! $billing->isSettled())
            <div class="card border-warning border-2 shadow-sm p-4 mb-4" id="settlement-receipt-form">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-1 fw-bold text-dark">
                            ثبت اطلاعات فیش واریزی و تسویه حساب (پیشنیاز چاپ برگه ۴ بخشی)
                        </h5>
                        <div class="text-muted small">
                            مبلغ قابل پرداخت این صورتحساب <strong class="text-dark money">{{ number_format($billing->total_amount) }} ریال</strong> می‌باشد. پس از وارد کردن مشخصات فیش واریزی، بلافاصله برگه ۴ بخشی تسویه حساب آماده چاپ خواهد شد.
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark fs-6">در انتظار ثبت فیش واریزی</span>
                </div>

                <form method="POST" action="{{ $settleUrl }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="receipt_number">
                                شماره فیش واریزی / کد رهگیری <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="receipt_number"
                                   id="receipt_number"
                                   value="{{ old('receipt_number', $billing->receipt_number) }}"
                                   class="form-control font-monospace @error('receipt_number') is-invalid @enderror"
                                   placeholder="شماره فیش یا شناسه پرداخت"
                                   autofocus
                                   required>
                            @error('receipt_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold" for="receipt_date_shamsi">
                                تاریخ واریز (شمسی) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="receipt_date_shamsi"
                                   id="receipt_date_shamsi"
                                   value="{{ old('receipt_date_shamsi', $defaultReceiptDate) }}"
                                   class="form-control jalali-datepicker @error('receipt_date_shamsi') is-invalid @enderror"
                                   autocomplete="off"
                                   required>
                            @error('receipt_date_shamsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="bank_name">
                                نام بانک / روش واریز
                            </label>
                            <input type="text"
                                   name="bank_name"
                                   id="bank_name"
                                   list="common-banks-list"
                                   value="{{ old('bank_name', $billing->bank_name) }}"
                                   class="form-control @error('bank_name') is-invalid @enderror"
                                   placeholder="مثلاً بانک ملی / کارتخوان / پایا">
                            @error('bank_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold" for="receipt_amount">
                                مبلغ فیش (ریال)
                            </label>
                            <input type="number"
                                   name="receipt_amount"
                                   id="receipt_amount"
                                   min="0"
                                   step="1"
                                   value="{{ old('receipt_amount', $billing->receipt_amount ?? $billing->total_amount) }}"
                                   class="form-control font-monospace @error('receipt_amount') is-invalid @enderror">
                            @error('receipt_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success fw-semibold w-100">
                                ثبت فیش و چاپ برگه ۴ بخشی
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="card border-success p-3 mb-4 bg-success-subtle" id="settlement-receipt-form">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex flex-wrap gap-4 align-items-center small">
                        <span class="badge bg-success fs-6">تسویه حساب و فیش واریزی ثبت شده</span>
                        <div><strong>شماره فیش / رهگیری:</strong> <span class="font-monospace fw-bold">{{ $billing->receipt_number }}</span></div>
                        <div><strong>تاریخ فیش واریزی:</strong> {{ $billing->receipt_date_shamsi ?: '—' }}</div>
                        <div><strong>بانک / روش واریز:</strong> {{ $billing->bank_name ?: '—' }}</div>
                        <div><strong>مبلغ فیش:</strong> <span class="money fw-bold">{{ number_format($billing->receipt_amount ?? $billing->total_amount) }} ریال</span></div>
                        @if($billing->settled_at)
                            <div><strong>زمان ثبت تسویه:</strong> @jdatetime($billing->settled_at)</div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('billing.print', $billing) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary fw-semibold">
                            چاپ مجدد برگه ۴ بخشی
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-receipt-collapse">
                            ویرایش فیش واریزی
                        </button>
                    </div>
                </div>

                <div class="collapse mt-3" id="edit-receipt-collapse">
                    <form method="POST" action="{{ $settleUrl }}" class="card card-body bg-white p-3">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">شماره فیش واریزی / کد رهگیری *</label>
                                <input type="text" name="receipt_number" value="{{ old('receipt_number', $billing->receipt_number) }}" class="form-control form-control-sm font-monospace" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">تاریخ واریز (شمسی) *</label>
                                <input type="text" name="receipt_date_shamsi" value="{{ old('receipt_date_shamsi', $defaultReceiptDate) }}" class="form-control form-control-sm jalali-datepicker" autocomplete="off" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">نام بانک / روش واریز</label>
                                <input type="text" name="bank_name" list="common-banks-list" value="{{ old('bank_name', $billing->bank_name) }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">مبلغ فیش (ریال)</label>
                                <input type="number" name="receipt_amount" min="0" step="1" value="{{ old('receipt_amount', $billing->receipt_amount ?? $billing->total_amount) }}" class="form-control form-control-sm font-monospace">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold">
                                    به‌روزرسانی و چاپ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <div class="card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3"><strong>صاحب کالا:</strong> {{ $billing->ownerName() }}</div>
            <div class="col-md-3"><strong>نماینده:</strong> {{ $billing->representativeName() ?? '-' }}</div>
            <div class="col-md-3"><strong>تاریخ صدور:</strong> {{ $billing->billing_date_shamsi }}</div>
            <div class="col-md-3"><strong>اپراتور:</strong> {{ $billing->operator?->name ?? '—' }}</div>
            @if($billing->cancelled_at)
                <div class="col-md-3"><strong>زمان لغو:</strong> @jdatetime($billing->cancelled_at)</div>
            @endif
        </div>
        @if($billing->notes)
            <div class="mt-3"><strong>توضیحات:</strong> {{ $billing->notes }}</div>
        @endif

        @php
            $editedItems = $billing->items->filter(fn ($i) => $i->wagon?->isOwnerEdited());
        @endphp
        @if($editedItems->isNotEmpty())
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <strong>ویرایش‌های ثبت‌شده برای واگن‌ها پس از صدور صورتحساب:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach($editedItems as $editedItem)
                        @php $w = $editedItem->wagon; @endphp
                        <li>
                            واگن <strong>{{ $w->wagon_number }}</strong>
                            @if($w->isNumberEdited() && $w->isOwnerChanged())
                                (اصلاح شماره واگن از «<span class="text-decoration-line-through">{{ $w->previous_wagon_number }}</span>» به «<strong>{{ $w->wagon_number }}</strong>» و تغییر صاحب کالا از «{{ $w->previousOwner?->name ?? '—' }}» به «<strong>{{ $w->ownerName() }}</strong>»)
                            @elseif($w->isNumberEdited())
                                (اصلاح شماره واگن از «<span class="text-decoration-line-through">{{ $w->previous_wagon_number }}</span>» به «<strong>{{ $w->wagon_number }}</strong>»)
                            @else
                                (تغییر صاحب کالا از «{{ $w->previousOwner?->name ?? '—' }}» به «<strong>{{ $w->ownerName() }}</strong>»)
                            @endif
                            — علت: {{ $w->owner_change_note }}
                            <span class="text-muted">
                                (@jdatetime($w->owner_changed_at){{ $w->ownerChangedBy ? ' توسط '.$w->ownerChangedBy->name : '' }})
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @php
        $groupedSummary = $billing->items
            ->groupBy(function ($item) {
                $tId = (string) ($item->tariff_id ?? 0);
                $tTitle = trim((string) $item->tariff_title);
                $tRate = (string) ($item->tariff_rate ?? 0);
                $weightKey = number_format((float) $item->loaded_weight, 2, '.', '');
                return $tId . '|' . $tTitle . '|' . $tRate . '|' . $weightKey;
            })
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'wagon_count' => $group->count(),
                    'wagon_numbers' => $group->map(fn ($i) => $i->wagon?->wagon_number ?? '—')->implode(' ، '),
                    'cargo_description' => $group->map(fn ($i) => trim((string) ($i->wagon?->cargo_description ?? '')))->filter()->unique()->implode('، ') ?: '—',
                    'tariff_title' => $first->tariff_title ?: ($first->tariff?->title ?? '—'),
                    'tariff_rate' => (int) ($first->tariff_rate ?: ($first->tariff?->rate ?? 0)),
                    'unit_weight' => (float) $first->loaded_weight,
                    'total_weight' => (float) $group->sum('loaded_weight'),
                    'base_cost' => (int) $group->sum('base_cost'),
                    'tax_amount' => (int) $group->sum('tax_amount'),
                    'total_amount' => (int) $group->sum('total_amount'),
                ];
            })
            ->values();
    @endphp

    @if($groupedSummary->isNotEmpty())
        <div class="card p-3 mb-3 border-primary">
            <h6 class="mb-2 fw-bold text-primary">
                خلاصه دسته‌بندی واگن‌ها بر اساس تعرفه و تناژ یکسان (مطابق برگه ۴ بخشی چاپ تسویه حساب)
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm bg-white align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ردیف</th>
                        <th>تعداد واگن</th>
                        <th>شماره واگن‌ها</th>
                        <th>شرح کالا</th>
                        <th>عنوان تعرفه استفاده شده</th>
                        <th>مبلغ تعرفه (ریال)</th>
                        <th>تناژ (واحد / کل)</th>
                        <th>هزینه پایه</th>
                        <th>مالیات</th>
                        <th>جمع تسویه</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupedSummary as $gIdx => $gRow)
                        <tr>
                            <td>{{ $gIdx + 1 }}</td>
                            <td class="fw-bold">{{ $gRow['wagon_count'] }}</td>
                            <td class="font-monospace fw-bold">{{ $gRow['wagon_numbers'] }}</td>
                            <td>{{ $gRow['cargo_description'] }}</td>
                            <td>{{ $gRow['tariff_title'] }}</td>
                            <td class="money">{{ number_format($gRow['tariff_rate']) }}</td>
                            <td>
                                {{ $gRow['unit_weight'] }} تن
                                @if($gRow['wagon_count'] > 1)
                                    <span class="text-muted small">(جمع: {{ $gRow['total_weight'] }} تن)</span>
                                @endif
                            </td>
                            <td class="money">{{ number_format($gRow['base_cost']) }}</td>
                            <td class="money">{{ number_format($gRow['tax_amount']) }}</td>
                            <td class="money fw-bold text-success">{{ number_format($gRow['total_amount']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="table-responsive mb-3">
        <table class="table table-bordered bg-white align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>شماره واگن</th>
                <th>شرح کالا</th>
                <th>صاحب کالا</th>
                <th>تعرفه</th>
                <th>ظرفیت (تن)</th>
                <th>وزن (تن)</th>
                <th>هزینه پایه</th>
                <th>مالیات</th>
                <th>جمع ردیف</th>
                @if(! $billing->isCancelled())
                    <th>ویرایش واگن</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($billing->items as $index => $item)
                @php
                    $wagon = $item->wagon;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="fw-bold">{{ $wagon?->wagon_number ?? '—' }}</div>
                        @if($wagon?->isNumberEdited())
                            <span class="badge bg-info text-dark mt-1">شماره اصلاح‌شده</span>
                            <div class="small text-muted mt-1">
                                قبلی: <span class="text-decoration-line-through">{{ $wagon->previous_wagon_number }}</span>
                            </div>
                            <div class="small text-muted">
                                علت: {{ $wagon->owner_change_note }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $wagon?->cargo_description ?: '—' }}</td>
                    <td>
                        <div>{{ $wagon?->ownerName() ?? $billing->ownerName() }}</div>
                        @if($wagon?->isOwnerChanged())
                            <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                            <div class="small text-muted mt-1">
                                قبلی: {{ $wagon->previousOwner?->name ?? '—' }}
                            </div>
                            <div class="small text-muted">
                                علت: {{ $wagon->owner_change_note }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $item->tariff_title }}</td>
                    <td>{{ $item->tariff_capacity }}</td>
                    <td>{{ $item->loaded_weight }}</td>
                    <td class="money">{{ number_format($item->base_cost) }}</td>
                    <td class="money">{{ number_format($item->tax_amount) }}</td>
                    <td class="money">{{ number_format($item->total_amount) }}</td>
                    @if(! $billing->isCancelled())
                        <td class="text-nowrap">
                            @if($wagon)
                                <a href="{{ \Illuminate\Support\Facades\Route::has('wagons.edit-owner') ? route('wagons.edit-owner', ['wagon' => $wagon, 'billing_id' => $billing->id]) : url('/wagons/'.$wagon->id.'/edit-owner?billing_id='.$billing->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    ویرایش واگن (شماره / صاحب کالا)
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $billing->isCancelled() ? 10 : 11 }}" class="text-center text-muted py-3">
                        ردیفی برای این صورتحساب ثبت نشده است (صورتحساب لغو شده).
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-md-5 offset-md-7">
            <table class="table table-sm bg-white">
                <tr>
                    <td>جمع هزینه پایه:</td>
                    <td class="text-end money">{{ number_format($billing->base_amount) }}</td>
                </tr>
                <tr>
                    <td>مالیات ({{ $billing->tax_percentage }}٪):</td>
                    <td class="text-end money">{{ number_format($billing->tax_amount) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td>مبلغ نهایی:</td>
                    <td class="text-end money">{{ number_format($billing->total_amount) }} ریال</td>
                </tr>
            </table>
        </div>
    </div>

    <a href="{{ route('billing.index') }}" class="btn btn-secondary">بازگشت به فهرست</a>
@endsection
