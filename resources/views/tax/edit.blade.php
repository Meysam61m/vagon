@extends('layouts.app')
@section('title', 'تنظیمات مالیات')

@section('content')
    <h4 class="mb-3">تنظیم درصد مالیات</h4>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4">
                <p class="mb-3">
                    درصد مالیات جاری:
                    <strong>{{ $current?->percentage ?? 0 }}٪</strong>
                </p>
                <form method="POST" action="{{ route('tax.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="percentage">درصد مالیات جدید</label>
                        <input type="number" step="0.01" min="0" max="100" name="percentage" id="percentage"
                               value="{{ old('percentage', $current?->percentage ?? 9) }}"
                               class="form-control @error('percentage') is-invalid @enderror" required>
                        @error('percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary" type="submit">بروزرسانی</button>
                </form>
                <p class="text-muted small mt-3 mb-0">
                    تغییر درصد روی صورتحساب‌های قبلی اثری ندارد؛ هر فاکتور درصد لحظه صدور خود را نگه می‌دارد.
                </p>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4">
                <h6 class="mb-3">تاریخچه تغییرات</h6>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                        <tr>
                            <th>درصد</th>
                            <th>تاریخ ثبت</th>
                            <th>ثبت‌کننده</th>
                            <th>وضعیت</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($history as $row)
                            <tr>
                                <td>{{ $row->percentage }}٪</td>
                                <td>@jdatetime($row->created_at)</td>
                                <td>{{ $row->updatedBy?->name ?? '—' }}</td>
                                <td>
                                    @if($row->is_current)
                                        <span class="badge bg-success">جاری</span>
                                    @else
                                        <span class="text-muted small">آرشیو</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">تاریخچه‌ای ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
