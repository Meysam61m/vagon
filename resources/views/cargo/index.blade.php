@extends('layouts.app')
@section('title', 'درخواست‌های بارگیری')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">درخواست‌های بارگیری</h4>
        <a href="{{ route('cargo.create') }}" class="btn btn-primary">ثبت درخواست جدید</a>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="search">جستجو</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control"
                       placeholder="شماره درخواست، نام صاحب کالا یا شماره واگن">
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
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">فیلتر</button>
                <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary">پاک کردن</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead>
            <tr>
                <th>شماره درخواست</th>
                <th>تاریخ</th>
                <th>صاحب کالا</th>
                <th>نماینده</th>
                <th>تعداد واگن</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($cargoRequests as $cargoRequest)
                @php
                    $badge = match ($cargoRequest->status) {
                        'billed' => 'bg-success',
                        'loaded' => 'bg-info text-dark',
                        default => 'bg-warning text-dark',
                    };
                @endphp
                <tr>
                    <td>{{ $cargoRequest->request_number }}</td>
                    <td>{{ $cargoRequest->request_date_shamsi }}</td>
                    <td>{{ $cargoRequest->owner?->name ?? '—' }}</td>
                    <td>{{ $cargoRequest->representative?->name ?? '—' }}</td>
                    <td>{{ $cargoRequest->wagons->count() }}</td>
                    <td><span class="badge {{ $badge }}">{{ $cargoRequest->statusLabel() }}</span></td>
                    <td>
                        <a href="{{ route('cargo.show', $cargoRequest) }}" class="btn btn-sm btn-outline-primary">مشاهده</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">درخواستی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $cargoRequests->links() }}
@endsection
