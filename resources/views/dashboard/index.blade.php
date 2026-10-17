@extends('layouts.app')
@section('title', 'داشبورد مدیریتی')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">داشبورد مدیریتی</h4>
        <span class="text-muted small">بازه: {{ $fromShamsi }} تا {{ $toShamsi }}</span>
    </div>

    <form method="GET" class="card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="from">از تاریخ (شمسی)</label>
                <input type="text" name="from" id="from" value="{{ $fromShamsi }}"
                       class="form-control jalali-datepicker" autocomplete="off" placeholder="۱۴۰۴/۰۷/۰۱">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="to">تا تاریخ (شمسی)</label>
                <input type="text" name="to" id="to" value="{{ $toShamsi }}"
                       class="form-control jalali-datepicker" autocomplete="off" placeholder="۱۴۰۴/۰۷/۳۰">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">۷ روز اخیر</a>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['total_requests']) }}</div>
                <div class="text-muted small">درخواست بارگیری</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['total_billings']) }}</div>
                <div class="text-muted small">صورتحساب صادرشده</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['total_amount']) }}</div>
                <div class="text-muted small">مجموع مبلغ (ریال)</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['pending_wagons']) }}</div>
                <div class="text-muted small">واگن در انتظار تسویه</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['active_owners']) }}</div>
                <div class="text-muted small">صاحبان کالای فعال</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card p-3 text-center h-100">
                <div class="kpi-value">{{ number_format($kpis['active_representatives']) }}</div>
                <div class="text-muted small">نمایندگان فعال</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="mb-3">بارگیری روزانه (تن)</h6>
                <canvas id="loadingChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="mb-3">درآمد روزانه (ریال)</h6>
                <canvas id="revenueChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="mb-3">سهم صاحبان کالا از مبلغ</h6>
                <canvas id="ownerShareChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center bg-light h-100">
                <div class="kpi-value">{{ number_format($wagonStats['loaded']) }}</div>
                <div class="text-muted small">واگن بارگیری‌شده (کل)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center bg-light h-100">
                <div class="kpi-value">{{ number_format($wagonStats['settled']) }}</div>
                <div class="text-muted small">واگن تسویه‌شده</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center bg-light h-100">
                <div class="kpi-value">{{ number_format($wagonStats['pending']) }}</div>
                <div class="text-muted small">در انتظار تسویه</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>آخرین صورتحساب‌ها</span>
            <a href="{{ route('reports.billings') }}" class="small">مشاهده گزارش کامل</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>شماره</th>
                    <th>صاحب کالا</th>
                    <th>تاریخ</th>
                    <th>مبلغ نهایی (ریال)</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentBillings as $billing)
                    <tr>
                        <td><a href="{{ route('billing.show', $billing) }}">{{ $billing->billing_number }}</a></td>
                        <td>{{ $billing->ownerName() }}</td>
                        <td>{{ $billing->billing_date_shamsi }}</td>
                        <td class="money">{{ number_format($billing->total_amount) }}</td>
                        <td>
                            <span class="badge {{ $billing->isCancelled() ? 'bg-secondary' : 'bg-success' }}">
                                {{ $billing->statusLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">هنوز صورتحسابی صادر نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            const loadingChart = @json($loadingChart);
            const revenueChart = @json($revenueChart);
            const ownerShare = @json($ownerShareChart);

            new Chart(document.getElementById('loadingChart'), {
                type: 'bar',
                data: {
                    labels: loadingChart.labels,
                    datasets: [{label: 'بارگیری (تن)', data: loadingChart.values, backgroundColor: '#0d6efd'}]
                },
                options: {responsive: true, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}}}
            });

            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: revenueChart.labels,
                    datasets: [{
                        label: 'درآمد (ریال)',
                        data: revenueChart.values,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,.15)',
                        fill: true,
                        tension: .3
                    }]
                },
                options: {responsive: true, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}}}
            });

            new Chart(document.getElementById('ownerShareChart'), {
                type: 'doughnut',
                data: {
                    labels: ownerShare.map(row => row.name),
                    datasets: [{data: ownerShare.map(row => Number(row.total)), backgroundColor: ['#0d6efd', '#6610f2', '#198754', '#fd7e14', '#dc3545', '#20c997', '#6c757d', '#ffc107']}]
                },
                options: {responsive: true}
            });
        });
    </script>
@endpush
