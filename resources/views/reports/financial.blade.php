@extends('layouts.app')
@section('title', 'گزارش مالی')

@section('content')
    <h4 class="mb-3">گزارش مالی</h4>
    @include('reports._date_filter')

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary['base']) }}</div>
                <div class="text-muted small">جمع مبلغ پایه (ریال)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary['tax']) }}</div>
                <div class="text-muted small">جمع مالیات (ریال)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary['total']) }}</div>
                <div class="text-muted small">جمع مبلغ نهایی (ریال)</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>تاریخ (شمسی)</th>
                <th>تعداد فاکتور</th>
                <th>مبلغ پایه</th>
                <th>مالیات</th>
                <th>مبلغ نهایی</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daily as $row)
                <tr>
                    <td>{{ $row->billing_date_shamsi }}</td>
                    <td>{{ number_format($row->billing_count) }}</td>
                    <td class="money">{{ number_format($row->base_amount) }}</td>
                    <td class="money">{{ number_format($row->tax_amount) }}</td>
                    <td class="money fw-bold">{{ number_format($row->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">در این بازه صورتحسابی صادر نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
