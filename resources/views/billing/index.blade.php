@extends('layouts.app')
@section('title', 'صورتحساب‌ها')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">صورتحساب‌ها</h4>
        <a href="{{ route('billing.create') }}" class="btn btn-primary">صدور صورتحساب جدید</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="شماره صورتحساب یا نام صاحب کالا">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="owner_id">صاحب کالا</label>
                <select name="owner_id" id="owner_id" class="form-select">
                    <option value="">همه</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) request('owner_id') === (string) $owner->id)>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">وضعیت</label>
                <select name="status" id="status" class="form-select">
                    <option value="">همه</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">فیلتر</button>
                <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead>
            <tr>
                <th>شماره</th>
                <th>صاحب کالا</th>
                <th>نماینده</th>
                <th>تاریخ</th>
                <th>مبلغ پایه</th>
                <th>مالیات</th>
                <th>مبلغ نهایی</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($billings as $billing)
                <tr>
                    <td>{{ $billing->billing_number }}</td>
                    <td>{{ $billing->ownerName() }}</td>
                    <td>{{ $billing->representativeName() ?? '-' }}</td>
                    <td>{{ $billing->billing_date_shamsi }}</td>
                    <td class="money">{{ number_format($billing->base_amount) }}</td>
                    <td class="money">{{ number_format($billing->tax_amount) }} ({{ $billing->tax_percentage }}٪)</td>
                    <td class="money fw-bold">{{ number_format($billing->total_amount) }}</td>
                    <td>
                        <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }}">
                            {{ $billing->statusLabel() }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('billing.show', $billing) }}" class="btn btn-sm btn-outline-primary">مشاهده</a>
                        <a href="{{ route('billing.print', $billing) }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">چاپ</a>
                        @if(auth()->user()->isAdmin() && ! $billing->isCancelled())
                            <form action="{{ route('billing.cancel', $billing) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('صورتحساب لغو شود؟ واگن‌ها آزاد می‌شوند و امکان صدور مجدد فراهم می‌شود.');">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">لغو</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">صورتحسابی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $billings->links() }}
@endsection
