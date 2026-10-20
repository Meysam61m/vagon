@extends('layouts.app')
@section('title', 'جزئیات صورتحساب')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="لوگو" class="bg-white border rounded p-1 shadow-sm" style="height: 46px; width: auto;">
            <div>
                <h4 class="mb-0">
                    صورتحساب: {{ $billing->billing_number }}
                    <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }} fs-6 align-middle">
                        {{ $billing->statusLabel() }}
                    </span>
                </h4>
                <div class="text-muted small">شرکت انبارهای عمومی و خدمات گمرکی ایران — مجتمع شهید مطهری</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('billing.print', $billing) }}" target="_blank" rel="noopener" class="btn btn-primary fw-semibold">
                چاپ برگه ۴ بخشی تسویه حساب
            </a>
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
