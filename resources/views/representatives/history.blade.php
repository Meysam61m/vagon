@extends('layouts.app')
@section('title', 'تاریخچه نماینده')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">تاریخچه نماینده: {{ $representative->name }}</h4>
        <a href="{{ route('representatives.edit', $representative) }}" class="btn btn-outline-primary">ویرایش اطلاعات</a>
    </div>

    <div class="card p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-3"><strong>کد ملی:</strong> {{ $representative->national_id ?? '-' }}</div>
            <div class="col-md-3"><strong>تلفن:</strong> {{ $representative->phone ?? '-' }}</div>
            <div class="col-md-3"><strong>وضعیت:</strong> {{ $representative->is_active ? 'فعال' : 'غیرفعال' }}</div>
            <div class="col-md-3">
                <strong>صاحبان کالا:</strong>
                {{ $representative->owners->pluck('name')->join('، ') ?: '-' }}
            </div>
        </div>
    </div>

    <h6>درخواست‌های بارگیری</h6>
    <div class="table-responsive mb-2">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>شماره درخواست</th>
                <th>تاریخ</th>
                <th>صاحب کالا</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            @forelse($cargoRequests as $request)
                <tr>
                    <td><a href="{{ route('cargo.show', $request) }}">{{ $request->request_number }}</a></td>
                    <td>{{ $request->request_date_shamsi }}</td>
                    <td>{{ $request->owner?->name ?? '-' }}</td>
                    <td>{{ $statusLabels[$request->status] ?? $request->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">درخواستی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $cargoRequests->links() }}

    <h6 class="mt-4">صورتحساب‌ها</h6>
    <div class="table-responsive mb-2">
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>شماره</th>
                <th>تاریخ</th>
                <th>صاحب کالا</th>
                <th>مبلغ نهایی</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            @forelse($billings as $billing)
                <tr>
                    <td><a href="{{ route('billing.show', $billing) }}">{{ $billing->billing_number }}</a></td>
                    <td>{{ $billing->billing_date_shamsi }}</td>
                    <td>{{ $billing->ownerName() }}</td>
                    <td class="money">{{ number_format($billing->total_amount) }}</td>
                    <td>{{ $billing->statusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">صورتحسابی صادر نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $billings->links() }}

    <a href="{{ route('representatives.index') }}" class="btn btn-secondary mt-3">بازگشت</a>
@endsection
