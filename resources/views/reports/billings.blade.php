@extends('layouts.app')
@section('title', 'گزارش صورتحساب‌ها')

@section('content')
    <h4 class="mb-3">گزارش صورتحساب‌های صادرشده</h4>
    @include('reports._date_filter')

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['count']) }}</div>
                <div class="text-muted small">تعداد فاکتور فعال</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['base']) }}</div>
                <div class="text-muted small">جمع مبلغ پایه (ریال)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['tax']) }}</div>
                <div class="text-muted small">جمع مالیات (ریال)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['total']) }}</div>
                <div class="text-muted small">جمع مبلغ نهایی (ریال)</div>
            </div>
        </div>
    </div>

    @if($totals['cancelled'] > 0)
        <div class="alert alert-warning py-2">
            {{ number_format($totals['cancelled']) }} صورتحساب لغوشده در این بازه وجود دارد که در جمع‌ها لحاظ نشده است.
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>شماره</th>
                <th>صاحب کالا</th>
                <th>نماینده</th>
                <th>شرح کالا</th>
                <th>اپراتور</th>
                <th>تاریخ</th>
                <th>مبلغ نهایی</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            @forelse($billings as $billing)
                <tr>
                    <td><a href="{{ route('billing.show', $billing) }}">{{ $billing->billing_number }}</a></td>
                    <td>{{ $billing->ownerName() }}</td>
                    <td>{{ $billing->representativeName() ?? '-' }}</td>
                    <td>
                        @php
                            $cargoNames = $billing->items
                                ->map(fn ($item) => $item->wagon?->cargo_description)
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        {{ $cargoNames->isEmpty() ? '—' : $cargoNames->implode('، ') }}
                    </td>
                    <td>{{ $billing->operator?->name ?? '—' }}</td>
                    <td>{{ $billing->billing_date_shamsi }}</td>
                    <td class="money">{{ number_format($billing->total_amount) }}</td>
                    <td>
                        <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }}">
                            {{ $billing->statusLabel() }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">صورتحسابی در این بازه نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $billings->links() }}
@endsection
