@extends('layouts.app')
@section('title', 'گزارش عملکرد نمایندگان')

@section('content')
    <h4 class="mb-3">گزارش عملکرد نمایندگان</h4>
    @include('reports._date_filter')

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>نماینده</th>
                <th>تعداد درخواست بارگیری</th>
                <th>مبلغ صورتحساب‌های صادرشده (ریال)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($stats as $row)
                <tr>
                    <td><a href="{{ route('representatives.history', $row->id) }}">{{ $row->name }}</a></td>
                    <td>{{ number_format($row->request_count) }}</td>
                    <td class="money">{{ number_format($row->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">نماینده‌ای ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
