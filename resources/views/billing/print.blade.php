<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چاپ برگه ۴ بخشی تسویه حساب و درخواست بارگیری — {{ $billing->billing_number }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="{{ asset('vendor/vazirmatn/Vazirmatn-font-face.css') }}" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 6mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            background: #f1f5f9;
            color: #000;
            direction: rtl;
            font-size: 8.5pt;
        }

        .print-toolbar {
            max-width: 210mm;
            margin: 12px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .print-toolbar .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-toolbar {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-print {
            background: #0d6efd;
            color: #fff;
        }

        .btn-next {
            background: #198754;
            color: #fff;
        }

        .btn-detail {
            background: #fff;
            color: #1e293b;
            border-color: #94a3b8;
        }

        .a4-sheet {
            width: 210mm;
            min-height: 286mm;
            margin: 0 auto 20px auto;
            background: #fff;
            padding: 4mm 5mm;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .slip-box {
            border: 1.2pt solid #000;
            border-radius: 3px;
            padding: 1.8mm 2.2mm;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #fff;
        }

        .slip-box.slip-primary {
            border-width: 1.5pt;
        }

        .cut-line {
            border-top: 1pt dashed #555;
            margin: 1.3mm 0;
            position: relative;
            text-align: center;
            height: 0;
        }

        .cut-line span {
            position: relative;
            top: -6px;
            background: #fff;
            padding: 0 6px;
            font-size: 7pt;
            color: #444;
        }

        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 0.8pt solid #000;
            padding-bottom: 1.2mm;
            margin-bottom: 1.2mm;
        }

        .slip-brand {
            display: flex;
            align-items: center;
            gap: 2mm;
            width: 31%;
        }

        .slip-brand img {
            height: 10.5mm;
            width: auto;
            object-fit: contain;
        }

        .slip-org-name {
            font-size: 7.5pt;
            font-weight: 800;
            line-height: 1.35;
        }

        .slip-org-sub {
            font-size: 6.8pt;
            color: #222;
            font-weight: 600;
        }

        .slip-title-center {
            text-align: center;
            width: 38%;
        }

        .slip-part-badge {
            display: inline-block;
            border: 0.8pt solid #000;
            background: #f1f5f9;
            padding: 0.5mm 2.5mm;
            border-radius: 2px;
            font-size: 8pt;
            font-weight: 800;
        }

        .slip-sub-purpose {
            font-size: 7pt;
            margin-top: 0.5mm;
            font-weight: 700;
        }

        .slip-meta {
            width: 31%;
            text-align: left;
            font-size: 7.3pt;
            line-height: 1.45;
        }

        .info-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            background: #f8fafc;
            border: 0.6pt solid #000;
            padding: 0.9mm 2mm;
            margin-bottom: 1.2mm;
            font-size: 7.5pt;
        }

        .status-pill {
            font-weight: 800;
            border: 0.6pt solid #000;
            padding: 0.2mm 1.5mm;
            background: #e2e8f0;
        }

        table.grouped-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.2mm;
            font-size: 7.3pt;
        }

        table.grouped-table th,
        table.grouped-table td {
            border: 0.6pt solid #000;
            padding: 0.8mm 1.2mm;
            text-align: center;
            vertical-align: middle;
        }

        table.grouped-table thead th {
            background: #e9ecef;
            font-weight: 800;
            font-size: 7.2pt;
            white-space: nowrap;
        }

        table.grouped-table tfoot td {
            background: #f1f5f9;
            font-weight: 800;
            border-top: 1pt solid #000;
        }

        .wagon-numbers-cell {
            font-family: monospace, 'Vazirmatn', Tahoma;
            font-weight: 800;
            font-size: 7.6pt;
            line-height: 1.3;
            word-break: break-word;
        }

        .money-cell {
            direction: ltr;
            font-weight: 700;
            white-space: nowrap;
        }

        .slip-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 2mm;
            margin-top: 0.8mm;
            font-size: 7.1pt;
        }

        .slip-declaration {
            width: 38%;
            line-height: 1.4;
            text-align: justify;
            font-size: 6.9pt;
            border-left: 0.6pt dotted #444;
            padding-left: 2mm;
        }

        .slip-signatures {
            width: 62%;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .sig-box {
            width: 32%;
        }

        .sig-title {
            font-weight: 800;
            margin-bottom: 4.5mm;
        }

        .sig-line {
            border-top: 0.5pt dotted #333;
            display: inline-block;
            min-width: 24mm;
            font-size: 6.7pt;
            color: #333;
            padding-top: 0.4mm;
        }

        @media print {
            html, body {
                background: #fff !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .a4-sheet {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .slip-part-badge,
            .info-strip,
            table.grouped-table thead th,
            table.grouped-table tfoot td,
            .status-pill {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

@php
    $activeCargo = $cargo ?? $billing->items->first()?->wagon?->cargoRequest;

    if ($activeCargo && $activeCargo->relationLoaded('wagons')) {
        $sourceItems = $activeCargo->wagons
            ->map(fn ($w) => $w->billingItem)
            ->filter()
            ->values();
    } else {
        $sourceItems = $billing->items;
    }

    // دسته‌بندی و خلاصه‌سازی واگن‌های شماره درخواست که از تعرفه و تناژ یکسان استفاده کرده‌اند در یک ردیف
    $groupedRows = $sourceItems
        ->groupBy(function ($item) {
            $tId = (string) ($item->tariff_id ?? 0);
            $tTitle = trim((string) $item->tariff_title);
            $tRate = (string) ($item->tariff_rate ?? 0);
            $weightKey = number_format((float) $item->loaded_weight, 2, '.', '');
            return $tId . '|' . $tTitle . '|' . $tRate . '|' . $weightKey;
        })
        ->map(function ($group) {
            $first = $group->first();
            $wagonNumbers = $group->map(fn ($i) => $i->wagon?->wagon_number ?? '—')->values()->all();
            $cargoDescriptions = $group->map(fn ($i) => trim((string) ($i->wagon?->cargo_description ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->implode('، ');
            $lineNumbers = $group->map(fn ($i) => trim((string) ($i->wagon?->line_number ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->implode('، ');

            $unitWeight = (float) $first->loaded_weight;
            $totalWeight = (float) $group->sum('loaded_weight');
            $tariffRate = (int) ($first->tariff_rate ?: ($first->tariff?->rate ?? 0));
            $tariffCapacity = (float) ($first->tariff_capacity ?: ($first->tariff?->capacity ?? 65));

            return [
                'wagon_count' => $group->count(),
                'wagon_numbers' => implode(' ، ', $wagonNumbers),
                'cargo_description' => $cargoDescriptions !== '' ? $cargoDescriptions : '—',
                'line_number' => $lineNumbers !== '' ? $lineNumbers : '—',
                'tariff_title' => $first->tariff_title ?: ($first->tariff?->title ?? '—'),
                'tariff_rate' => $tariffRate,
                'tariff_capacity' => $tariffCapacity,
                'unit_weight' => $unitWeight,
                'total_weight' => $totalWeight,
                'base_cost' => (int) $group->sum('base_cost'),
                'tax_amount' => (int) $group->sum('tax_amount'),
                'total_amount' => (int) $group->sum('total_amount'),
            ];
        })
        ->values();

    $requestNumbers = $activeCargo?->request_number ?: $sourceItems
        ->map(fn ($i) => $i->wagon?->cargoRequest?->request_number)
        ->filter()
        ->unique()
        ->values()
        ->implode('، ');

    if ($requestNumbers === '') {
        $requestNumbers = '—';
    }

    $distinctBillings = $sourceItems
        ->map(fn ($i) => $i->billing)
        ->filter()
        ->unique('id')
        ->values();

    if ($distinctBillings->isEmpty()) {
        $distinctBillings = collect([$billing]);
    }

    $billingNumbersStr = $distinctBillings->pluck('billing_number')->filter()->unique()->implode(' ، ') ?: $billing->billing_number;
    $ownerNamesStr = $sourceItems
        ->map(fn ($i) => $i->wagon?->ownerName() ?? $i->billing?->ownerName())
        ->filter()
        ->unique()
        ->implode(' ، ') ?: $billing->ownerName();
    $repNameStr = $activeCargo?->representative?->name
        ?: ($billing->representativeName() ?? '—');
    $operatorNameStr = $distinctBillings->last()?->operator?->name
        ?: ($billing->operator?->name ?? 'امضا');

    $receiptNumbersStr = $distinctBillings->pluck('receipt_number')->filter()->unique()->implode(' ، ') ?: ($billing->receipt_number ?: '—');
    $receiptDatesStr = $distinctBillings->pluck('receipt_date_shamsi')->filter()->unique()->implode(' ، ') ?: ($billing->receipt_date_shamsi ?: $billing->billing_date_shamsi);
    $bankNamesStr = $distinctBillings->pluck('bank_name')->filter()->unique()->implode(' ، ') ?: ($billing->bank_name ?: '');

    $sumBaseAmount = (int) $sourceItems->sum('base_cost');
    $sumTaxAmount = (int) $sourceItems->sum('tax_amount');
    $sumTotalAmount = (int) $sourceItems->sum('total_amount');
    $sumReceiptAmount = (int) $distinctBillings->sum(fn ($b) => (int) ($b->receipt_amount ?? $b->total_amount));

    $totalWagonCount = $sourceItems->count();
    $totalWeightSum = (float) $sourceItems->sum('loaded_weight');
    $totalWeightFormatted = floor($totalWeightSum) == $totalWeightSum
        ? (string) ((int) $totalWeightSum)
        : number_format($totalWeightSum, 2);

    $sections = [
        [
            'number' => 1,
            'title' => 'بخش اول: درخواست بارگیری و برگ تسویه حساب واگن',
            'subtitle' => 'نسخه بایگانی — پس از اخذ امضای نماینده صاحب کالا بایگانی گردد',
            'declaration' => 'اینجانب نماینده صاحب کالا، ضمن درخواست بارگیری و تأیید شماره واگن‌ها، تناژ و تعرفه مندرج در جدول فوق، تسویه حساب کامل تمام واگن‌های این شماره درخواست را گواهی می‌نمایم.',
            'sig1' => 'نام و امضای نماینده صاحب کالا (جهت بایگانی)',
            'sig2' => 'امضای متصدی بارگیری / صادرکننده',
            'sig3' => 'مهر و امضای مسئول عملیات / بایگانی',
            'primary' => true,
        ],
        [
            'number' => 2,
            'title' => 'بخش دوم: برگ تسویه حساب و مشخصات بارگیری واگن',
            'subtitle' => 'نسخه امور مالی و صندوق',
            'declaration' => 'مبالغ پایه و عوارض ارزش افزوده تمام واگن‌های شماره درخواست فوق بر اساس تعرفه و تناژ ثبت‌شده محاسبه و تسویه گردید.',
            'sig1' => 'امضای نماینده صاحب کالا',
            'sig2' => 'امضای متصدی صدور صورتحساب',
            'sig3' => 'مهر و امضای امور مالی / صندوق',
            'primary' => false,
        ],
        [
            'number' => 3,
            'title' => 'بخش سوم: برگ تسویه حساب و مشخصات بارگیری واگن',
            'subtitle' => 'نسخه صاحب کالا / نماینده',
            'declaration' => 'این برگه به منزله گواهی تسویه حساب واگن‌های مندرج در جدول با مشخصات تناژ و تعرفه ثبت‌شده می‌باشد.',
            'sig1' => 'امضای نماینده صاحب کالا',
            'sig2' => 'امضای متصدی بارگیری / صدور',
            'sig3' => 'مهر و امضای واحد صدور صورتحساب',
            'primary' => false,
        ],
        [
            'number' => 4,
            'title' => 'بخش چهارم: برگ تسویه حساب و مجوز عملیات واگن',
            'subtitle' => 'نسخه عملیات بارگیری / انتظامات',
            'declaration' => 'عملیات بارگیری و تسویه حساب کامل تمام واگن‌های شماره درخواست فوق انجام شده و بلامانع است.',
            'sig1' => 'امضای نماینده صاحب کالا',
            'sig2' => 'امضای متصدی عملیات بارگیری',
            'sig3' => 'مهر و امضای رئیس عملیات / انتظامات',
            'primary' => false,
        ],
    ];
@endphp

<div class="print-toolbar">
    <div>
        <strong>برگه ۴ بخشی بر اساس شماره درخواست بارگیری: {{ $requestNumbers }}</strong>
        <span style="font-size: 12px; color: #475569; margin-right: 8px;">
            (شماره صورتحساب: {{ $billingNumbersStr }} | {{ $totalWagonCount }} واگن خلاصه‌شده در {{ $groupedRows->count() }} ردیف تعرفه/تناژ یکسان)
        </span>
    </div>
    <div class="actions">
        <button type="button" class="btn-toolbar btn-print" onclick="window.print()">
            چاپ برگه ۴ بخشی (A4)
        </button>
        @if($activeCargo)
            <a href="{{ route('cargo.show', $activeCargo) }}" class="btn-toolbar btn-detail">
                مشاهده درخواست {{ $activeCargo->request_number }}
            </a>
        @endif
        <a href="{{ route('billing.create') }}" class="btn-toolbar btn-next">
            + صدور صورتحساب بعدی
        </a>
        <a href="{{ route('billing.show', $billing) }}" class="btn-toolbar btn-detail">
            مشاهده صورتحساب
        </a>
        <a href="{{ route('billing.index') }}" class="btn-toolbar btn-detail">
            لیست صورتحساب‌ها
        </a>
    </div>
</div>

<div class="a4-sheet">
    @foreach($sections as $sIdx => $sec)
        <div class="slip-box {{ $sec['primary'] ? 'slip-primary' : '' }}">
            <div class="slip-header">
                <div class="slip-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="لوگو">
                    <div>
                        <div class="slip-org-name">شرکت انبارهای عمومی و خدمات گمرکی ایران</div>
                        <div class="slip-org-sub">مجتمع شهید مطهری — عملیات بارگیری واگن</div>
                    </div>
                </div>

                <div class="slip-title-center">
                    <div class="slip-part-badge">{{ $sec['title'] }}</div>
                    <div class="slip-sub-purpose">{{ $sec['subtitle'] }}</div>
                </div>

                <div class="slip-meta">
                    <div><strong>شماره درخواست (ملاک):</strong> {{ $requestNumbers }}</div>
                    <div><strong>شماره صورتحساب:</strong> {{ $billingNumbersStr }}</div>
                    <div><strong>تاریخ تسویه:</strong> {{ $receiptDatesStr }}</div>
                </div>
            </div>

            <div class="info-strip">
                <div><strong>صاحب کالا:</strong> {{ $ownerNamesStr }}</div>
                <div><strong>نماینده:</strong> {{ $repNameStr }}</div>
                <div><strong>شماره فیش واریزی:</strong> {{ $receiptNumbersStr }}</div>
                <div><strong>تاریخ فیش:</strong> {{ $receiptDatesStr }}</div>
                @if($bankNamesStr !== '')
                    <div><strong>بانک/روش:</strong> {{ $bankNamesStr }}</div>
                @endif
                <div><strong>مبلغ فیش:</strong> {{ number_format($sumReceiptAmount ?: $sumTotalAmount) }} ریال</div>
                <div>
                    <span class="status-pill">
                        وضعیت درخواست: تسویه کامل ({{ $totalWagonCount }} واگن)
                    </span>
                </div>
            </div>

            <table class="grouped-table">
                <thead>
                <tr>
                    <th style="width: 4%;">ردیف</th>
                    <th style="width: 5%;">تعداد واگن</th>
                    <th style="width: 20%;">شماره واگن‌ها (دسته‌بندی‌شده)</th>
                    <th style="width: 10%;">شرح کالا</th>
                    <th style="width: 6%;">شماره خط</th>
                    <th style="width: 17%;">عنوان تعرفه استفاده شده</th>
                    <th style="width: 9%;">مبلغ تعرفه (ریال)</th>
                    <th style="width: 8%;">تناژ (واحد / کل)</th>
                    <th style="width: 7%;">مبلغ پایه</th>
                    <th style="width: 6%;">مالیات ({{ (float) $billing->tax_percentage }}٪)</th>
                    <th style="width: 8%;">جمع کل (ریال)</th>
                </tr>
                </thead>
                <tbody>
                @forelse($groupedRows as $gIdx => $gRow)
                    @php
                        $unitWStr = floor($gRow['unit_weight']) == $gRow['unit_weight']
                            ? (string) ((int) $gRow['unit_weight'])
                            : number_format($gRow['unit_weight'], 2);
                        $totWStr = floor($gRow['total_weight']) == $gRow['total_weight']
                            ? (string) ((int) $gRow['total_weight'])
                            : number_format($gRow['total_weight'], 2);
                    @endphp
                    <tr>
                        <td>{{ $gIdx + 1 }}</td>
                        <td><strong>{{ $gRow['wagon_count'] }}</strong></td>
                        <td class="wagon-numbers-cell">{{ $gRow['wagon_numbers'] }}</td>
                        <td>{{ $gRow['cargo_description'] }}</td>
                        <td>{{ $gRow['line_number'] }}</td>
                        <td>{{ $gRow['tariff_title'] }}</td>
                        <td class="money-cell">{{ number_format($gRow['tariff_rate']) }}</td>
                        <td>
                            @if($gRow['wagon_count'] > 1)
                                {{ $unitWStr }} تن (جمع: {{ $totWStr }})
                            @else
                                {{ $unitWStr }} تن
                            @endif
                        </td>
                        <td class="money-cell">{{ number_format($gRow['base_cost']) }}</td>
                        <td class="money-cell">{{ number_format($gRow['tax_amount']) }}</td>
                        <td class="money-cell">{{ number_format($gRow['total_amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">ردیفی ثبت نشده است.</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2">جمع کل درخواست:</td>
                    <td>{{ $totalWagonCount }} واگن</td>
                    <td colspan="4" style="text-align: left; padding-left: 6px;">مجموع تناژ و مبالغ تسویه حساب (ریال):</td>
                    <td>{{ $totalWeightFormatted }} تن</td>
                    <td class="money-cell">{{ number_format($sumBaseAmount ?: $billing->base_amount) }}</td>
                    <td class="money-cell">{{ number_format($sumTaxAmount ?: $billing->tax_amount) }}</td>
                    <td class="money-cell">{{ number_format($sumTotalAmount ?: $billing->total_amount) }}</td>
                </tr>
                </tfoot>
            </table>

            <div class="slip-footer">
                <div class="slip-declaration">
                    {{ $sec['declaration'] }}
                    @if($billing->notes)
                        <br><strong>توضیحات:</strong> {{ $billing->notes }}
                    @endif
                </div>
                <div class="slip-signatures">
                    <div class="sig-box">
                        <div class="sig-title">{{ $sec['sig1'] }}</div>
                        <div class="sig-line">{{ $repNameStr }}</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-title">{{ $sec['sig2'] }}</div>
                        <div class="sig-line">{{ $operatorNameStr }}</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-title">{{ $sec['sig3'] }}</div>
                        <div class="sig-line">مهر و امضا</div>
                    </div>
                </div>
            </div>
        </div>

        @if(! $loop->last)
            <div class="cut-line">
                <span>✂ خط برش بخش {{ $sec['number'] }}</span>
            </div>
        @endif
    @endforeach
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
