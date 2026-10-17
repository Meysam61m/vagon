@extends('layouts.app')
@section('title', 'گزارش روزانه بارگیری')

@section('content')
    <h4 class="mb-3">گزارش روزانه بارگیری‌ها</h4>
    @include('reports._date_filter')

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary->wagon_count) }}</div>
                <div class="text-muted small">تعداد واگن در بازه</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($summary->loaded_count) }}</div>
                <div class="text-muted small">واگن بارگیری‌شده</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format((float) $summary->total_weight, 1) }}</div>
                <div class="text-muted small">مجموع وزن بارگیری (تن)</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>شماره درخواست</th>
                <th>تاریخ</th>
                <th>صاحب کالا</th>
                <th>نماینده</th>
                <th>شرح کالا</th>
                <th>تعداد واگن</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $request)
                <tr>
                    <td><a href="{{ route('cargo.show', $request) }}">{{ $request->request_number }}</a></td>
                    <td>{{ $request->request_date_shamsi }}</td>
                    <td>{{ $request->owner?->name ?? '—' }}</td>
                    <td>{{ $request->representative?->name ?? '—' }}</td>
                    <td>
                        @php
                            $cargoNames = $request->wagons
                                ->pluck('cargo_description')
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        {{ $cargoNames->isEmpty() ? '—' : $cargoNames->implode('، ') }}
                    </td>
                    <td>{{ $request->wagons->count() }}</td>
                    <td>{{ $request->statusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">در این بازه درخواستی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}
@endsection
