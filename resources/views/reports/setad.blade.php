@extends('layouts.app')
@section('title', 'گزارش ستاد')

@push('styles')
    <style>
        .setad-table th,
        .setad-table td {
            text-align: center;
            vertical-align: middle;
            font-size: 0.88rem;
            border-color: #333 !important;
        }

        .setad-table thead .setad-banner th {
            font-size: 1.05rem;
            font-weight: 700;
            background-color: #fff;
            border-bottom: 2px solid #000 !important;
            padding: 12px;
        }

        .setad-table thead .setad-headers th {
            background-color: #f8f9fa;
            font-weight: 700;
            white-space: nowrap;
        }

        .setad-green-col {
            background-color: #92D050 !important;
            font-weight: 700;
            color: #000;
        }

        .setad-notes-cell {
            max-width: 280px;
            font-size: 0.8rem;
            line-height: 1.6;
        }

        .setad-signatures {
            margin-top: 32px;
            font-weight: 700;
        }

        @media print {
            nav.navbar,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .setad-green-col {
                background-color: #92D050 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <h4 class="mb-0">گزارش ستاد (عملیات فاز ۲ شهید مطهری)</h4>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
                دانلود فایل اکسل گزارش ستاد (.xlsx)
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                چاپ گزارش
            </button>
        </div>
    </div>

    <div class="no-print">
        @include('reports._date_filter')
    </div>

    <div class="row g-3 mb-3 no-print">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['count']) }}</div>
                <div class="text-muted small">تعداد کل واگن‌ها در بازه</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['total_with_vat']) }}</div>
                <div class="text-muted small">جمع مبلغ با احتساب ارزش افزوده (ریال)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center" style="border-color: #92D050;">
                <div class="kpi-value">{{ number_format(round($totals['booklet_rate'])) }}</div>
                <div class="text-muted small">جمع مبلغ تعرفه در کتابچه (ریال)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="kpi-value">{{ number_format($totals['tonnage_count']) }}</div>
                <div class="text-muted small">تعداد واگن‌های محاسبه تناژی</div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-bordered bg-white setad-table mb-0">
                <thead>
                <tr class="setad-banner">
                    <th colspan="12">
                        لیست واگن های فاز 2 شهید مطهری در بازه زمانی {{ $fromShamsi }} لغایت {{ $toShamsi }}
                    </th>
                </tr>
                <tr class="setad-headers">
                    <th>ردیف</th>
                    <th>شماره واگن</th>
                    <th>تعرفه</th>
                    <th>نام صاحب کالا</th>
                    <th>مبلغ با احتساب مالیات بر ارزش افزوده</th>
                    <th>مبلغ کل قابل دریافت از مشتری</th>
                    <th>توضیحات/ شماره وسیله حمل</th>
                    <th>مبلغ تعرفه در کتابچه</th>
                    <th>تناژ (عملیاتی)</th>
                    <th>محاسبه بر اساس کتابچه تعرفه</th>
                    <th>نحوه محاسبه</th>
                    <th>توضیحات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="fw-bold">{{ $row['index'] }}</td>
                        <td class="fw-bold">{{ $row['wagon_number'] }}</td>
                        <td>{{ $row['tariff_name'] }}</td>
                        <td>{{ $row['owner_name'] }}</td>
                        <td class="money fw-bold">{{ number_format($row['total_with_vat']) }}</td>
                        <td class="money">
                            {{ $row['customer_payable'] !== null ? number_format(round($row['customer_payable'])) : '' }}
                        </td>
                        <td>{{ $row['equipment_label'] }}</td>
                        <td class="money setad-green-col">
                            {{ number_format(round($row['booklet_rate'])) }}
                        </td>
                        <td>
                            @if($row['operational_tonnage'] !== null)
                                {{ floor($row['operational_tonnage']) == $row['operational_tonnage'] ? (int) $row['operational_tonnage'] : $row['operational_tonnage'] }}
                            @endif
                        </td>
                        <td class="money">
                            {{ $row['per_ton_rate'] !== null ? number_format(round($row['per_ton_rate'])) : '' }}
                        </td>
                        <td class="text-nowrap">{{ $row['calc_method'] }}</td>
                        <td class="setad-notes-cell">{{ $row['notes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            در بازه زمانی {{ $fromShamsi }} لغایت {{ $toShamsi }} صورتحساب فعالی ثبت نشده است.
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if(count($rows) > 0)
                    <tfoot>
                    <tr class="table-warning fw-bold">
                        <td colspan="4">جمع کل ({{ number_format($totals['count']) }} واگن)</td>
                        <td class="money">{{ number_format($totals['total_with_vat']) }}</td>
                        <td class="money">
                            {{ $totals['customer_payable'] > 0 ? number_format(round($totals['customer_payable'])) : '' }}
                        </td>
                        <td></td>
                        <td class="money setad-green-col">{{ number_format(round($totals['booklet_rate'])) }}</td>
                        <td>{{ $totals['operational_tonnage'] > 0 ? number_format($totals['operational_tonnage'], 1) : '' }}</td>
                        <td colspan="3"></td>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="row text-center setad-signatures py-4">
            <div class="col-6 col-md-3 mb-4">
                <div>امضاء طرف قرار داد / پیمانکار</div>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <div>امضاء مدیر مجتمع</div>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <div>امضاء مدیر شعبه</div>
            </div>
            <div class="col-6 col-md-3 mb-4">
                <div>امضاء رئیس عملیات</div>
            </div>
        </div>
    </div>
@endsection
