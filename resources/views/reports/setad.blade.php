@extends('layouts.app')
@section('title', 'گزارش ستاد')

@push('styles')
    <style>
        .setad-sheet-wrapper {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 16px;
        }

        .setad-table {
            border-collapse: collapse !important;
            width: 100%;
        }

        .setad-table th,
        .setad-table td {
            text-align: center;
            vertical-align: middle;
            font-size: 0.86rem;
            border: 1px solid #222 !important;
            padding: 7px 8px;
            color: #000;
        }

        .setad-table thead .setad-banner th {
            font-size: 1.05rem;
            font-weight: 700;
            background-color: #fff;
            border: 1px solid #222 !important;
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
            color: #000 !important;
        }

        .setad-total-row td {
            background-color: #f1f5f9;
            font-weight: 700;
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
        }

        .setad-total-row td.setad-green-col {
            background-color: #92D050 !important;
        }

        .setad-notes-cell {
            max-width: 240px;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .setad-signatures {
            margin-top: 36px;
            font-weight: 700;
            color: #000;
        }

        @media print {
            @page {
                size: landscape;
                margin: 8mm;
            }

            nav.navbar,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .setad-sheet-wrapper {
                border: none !important;
                padding: 0 !important;
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
            <button type="button" class="btn btn-primary" onclick="window.print()">
                چاپ گزارش
            </button>
        </div>
    </div>

    <div class="no-print">
        @include('reports._date_filter')
    </div>

    <div class="setad-sheet-wrapper">
        <div class="table-responsive">
            <table class="table table-bordered bg-white setad-table mb-0">
                <thead>
                <tr class="setad-banner">
                    <th colspan="13">
                        <div class="d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex align-items-center gap-2 text-start">
                                <img src="{{ asset('images/logo.png') }}" alt="لوگو" style="height: 44px; width: auto;">
                                <div class="small fw-bold" style="line-height: 1.4;">
                                    <div>شرکت انبارهای عمومی و خدمات گمرکی ایران</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">مجتمع شهید مطهری</div>
                                </div>
                            </div>
                            <div>
                                لیست واگن های فاز 2 شهید مطهری در بازه زمانی {{ $fromShamsi }} لغایت {{ $toShamsi }}
                            </div>
                            <div class="small text-muted" style="min-width: 110px;">
                                تاریخ گزارش: {{ \App\Support\JalaliDate::today() }}
                            </div>
                        </div>
                    </th>
                </tr>
                <tr class="setad-headers">
                    <th>ردیف</th>
                    <th>شماره واگن</th>
                    <th>شرح کالا</th>
                    <th>عنوان تعرفه استفاده شده</th>
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
                        <td class="fw-bold font-monospace">{{ $row['wagon_number'] }}</td>
                        <td>{{ $row['cargo_description'] ?? $row['tariff_name'] }}</td>
                        <td>{{ $row['used_tariff_title'] ?? '—' }}</td>
                        <td>{{ $row['owner_name'] }}</td>
                        <td class="money fw-bold">{{ number_format(round($row['total_with_vat'])) }}</td>
                        <td class="money fw-bold">{{ number_format(round($row['customer_payable'])) }}</td>
                        <td>{{ $row['equipment_label'] }}</td>
                        <td class="money setad-green-col">{{ number_format(round($row['booklet_rate'])) }}</td>
                        <td class="fw-bold">
                            {{ floor($row['operational_tonnage']) == $row['operational_tonnage'] ? (int) $row['operational_tonnage'] : number_format($row['operational_tonnage'], 2) }}
                        </td>
                        <td class="money">{{ number_format(round($row['per_ton_rate'])) }}</td>
                        <td class="text-nowrap">{{ $row['calc_method'] }}</td>
                        <td class="setad-notes-cell">{{ $row['notes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">
                            در بازه زمانی {{ $fromShamsi }} لغایت {{ $toShamsi }} واگن بارگیری‌شده یا صورتحساب فعالی ثبت نشده است.
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if(count($rows) > 0)
                    <tfoot>
                    <tr class="setad-total-row">
                        <td colspan="4">جمع کل ({{ number_format($totals['count']) }} واگن)</td>
                        <td>—</td>
                        <td class="money">{{ number_format(round($totals['total_with_vat'])) }}</td>
                        <td class="money">{{ number_format(round($totals['customer_payable'])) }}</td>
                        <td>—</td>
                        <td class="money setad-green-col">{{ number_format(round($totals['booklet_rate'])) }}</td>
                        <td>
                            {{ floor($totals['operational_tonnage']) == $totals['operational_tonnage'] ? number_format((int) $totals['operational_tonnage']) : number_format($totals['operational_tonnage'], 2) }}
                        </td>
                        <td class="money">{{ number_format(round($totals['per_ton_rate'] ?? 0)) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="setad-signatures">
            <div class="row text-center mb-5">
                <div class="col-6">
                    <div>امضاء طرف قرار داد / پیمانکار</div>
                </div>
                <div class="col-6">
                    <div>امضاء مدیر شعبه</div>
                </div>
            </div>
            <div class="row text-center pb-3">
                <div class="col-6">
                    <div>امضاء مدیر مجتمع</div>
                </div>
                <div class="col-6">
                    <div>امضاء رئیس عملیات</div>
                </div>
            </div>
        </div>
    </div>
@endsection
