@extends('layouts.app')
@section('title', 'گزارش عملکرد صاحبان کالا')

@section('content')
    <h4 class="mb-3">گزارش عملکرد صاحبان کالا</h4>
    @include('reports._date_filter')

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>صاحب کالا</th>
                <th>تعداد صورتحساب</th>
                <th>جمع مبلغ پایه</th>
                <th>جمع مالیات</th>
                <th>جمع مبلغ پرداختی</th>
            </tr>
            </thead>
            <tbody>
            @forelse($stats as $row)
                <tr>
                    <td>
                        <a href="{{ route('owners.history', $row->id) }}">{{ $row->name }}</a>
                    </td>
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
