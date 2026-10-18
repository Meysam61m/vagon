@extends('layouts.app')
@section('title', 'گزارش تفصیلی واگن‌ها')

@section('content')
    <h4 class="mb-3">گزارش تفصیلی واگن‌ها</h4>
    @include('reports._date_filter')

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format((float) $totals->weight, 1) }}</div>
                <div class="text-muted small">مجموع وزن (تن)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals->base) }}</div>
                <div class="text-muted small">جمع هزینه پایه</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals->tax) }}</div>
                <div class="text-muted small">جمع مالیات ردیف‌ها</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals->total) }}</div>
                <div class="text-muted small">جمع مبلغ ردیف‌ها</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
            <tr>
                <th>شماره واگن</th>
                <th>شرح کالا</th>
                <th>صاحب کالا</th>
                <th>فاکتور</th>
                <th>تعرفه</th>
                <th>وزن (تن)</th>
                <th>هزینه پایه</th>
                <th>مالیات ردیف</th>
                <th>جمع ردیف</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $wagon = $item->wagon;
                @endphp
                <tr>
                    <td>{{ $wagon?->wagon_number ?? '—' }}</td>
                    <td>{{ $wagon?->cargo_description ?: '—' }}</td>
                    <td>
                        <div>{{ $wagon?->ownerName() ?? $item->billing?->ownerName() ?? '—' }}</div>
                        @if($wagon?->isOwnerEdited())
                            <span class="badge bg-warning text-dark mt-1">ویرایش‌شده</span>
                            <div class="small text-muted">قبلی: {{ $wagon->previousOwner?->name ?? '—' }}</div>
                            <div class="small text-muted">توضیح: {{ $wagon->owner_change_note }}</div>
                        @endif
                    </td>
                    <td>
                        @if($item->billing)
                            <a href="{{ route('billing.show', $item->billing) }}">{{ $item->billing->billing_number }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $item->tariff_title }}</td>
                    <td>{{ $item->loaded_weight }}</td>
                    <td class="money">{{ number_format($item->base_cost) }}</td>
                    <td class="money">{{ number_format($item->tax_amount) }}</td>
                    <td class="money">{{ number_format($item->total_amount) }}</td>
                    <td class="text-nowrap">
                        @if($wagon)
                            <a href="{{ route('wagons.edit-owner', $wagon) }}" class="btn btn-sm btn-outline-primary">
                                ویرایش صاحب کالا
                            </a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">ردیفی در این بازه نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
@endsection
