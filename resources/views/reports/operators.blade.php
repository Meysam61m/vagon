@extends('layouts.app')
@section('title', 'گزارش عملکرد اپراتورها')

@section('content')
    <h4 class="mb-3">گزارش عملکرد اپراتورها</h4>
    @include('reports._date_filter')

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>اپراتور</th>
                <th>تعداد صورتحساب</th>
                <th>جمع مبلغ (ریال)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($stats as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ number_format($row->billing_count) }}</td>
                    <td class="money">{{ number_format($row->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">در این بازه صورتحسابی صادر نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
