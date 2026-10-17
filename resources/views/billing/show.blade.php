@extends('layouts.app')
@section('title', 'جزئیات صورتحساب')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            صورتحساب: {{ $billing->billing_number }}
            <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }} fs-6 align-middle">
                {{ $billing->statusLabel() }}
            </span>
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('billing.print', $billing) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                چاپ PDF
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
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>#</th>
                <th>شماره واگن</th>
                <th>شرح کالا</th>
                <th>تعرفه</th>
                <th>ظرفیت (تن)</th>
                <th>وزن (تن)</th>
                <th>هزینه پایه</th>
                <th>مالیات</th>
                <th>جمع ردیف</th>
            </tr>
            </thead>
            <tbody>
            @forelse($billing->items as $index => $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->wagon?->wagon_number ?? '—' }}</td>
                    <td>{{ $item->wagon?->cargo_description ?: '—' }}</td>
                    <td>{{ $item->tariff_title }}</td>
                    <td>{{ $item->tariff_capacity }}</td>
                    <td>{{ $item->loaded_weight }}</td>
                    <td class="money">{{ number_format($item->base_cost) }}</td>
                    <td class="money">{{ number_format($item->tax_amount) }}</td>
                    <td class="money">{{ number_format($item->total_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">
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
