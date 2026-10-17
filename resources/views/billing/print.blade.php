@php
    // ساخت URL فایل فونت به‌صورت سازگار با ویندوز و لینوکس (file:///C:/... و file:///var/...)
    $fontUrl = function (string $path): string {
        $path = str_replace('\\', '/', $path);

        return 'file://'.(str_starts_with($path, '/') ? '' : '/').str_replace(' ', '%20', $path);
    };

    $regularFont = $fontUrl(storage_path('fonts/Vazirmatn-Regular.ttf'));
    $boldFont = $fontUrl(storage_path('fonts/Vazirmatn-Bold.ttf'));
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>صورتحساب {{ $billing->billing_number }}</title>
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url("{{ $regularFont }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Vazirmatn';
            src: url("{{ $boldFont }}") format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            font-size: 13px;
            padding: 24px;
            background: #fff;
            color: #111;
            direction: rtl;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .header .sub {
            font-size: 13px;
            color: #555;
        }

        .status {
            margin-top: 6px;
            font-size: 12px;
        }

        table.info {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        table.info td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            text-align: right;
            width: 25%;
        }

        table.info td.label {
            background: #f5f5f5;
            font-weight: bold;
            width: 15%;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        table.items th,
        table.items td {
            border: 1px solid #444;
            padding: 6px;
            text-align: center;
        }

        table.items th {
            background: #efefef;
            font-weight: bold;
        }

        table.totals {
            width: 55%;
            margin-right: 0;
            margin-left: auto;
            margin-top: 12px;
            border-collapse: collapse;
        }

        table.totals td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            text-align: right;
        }

        table.totals td.amount {
            text-align: left;
            direction: ltr;
        }

        table.totals tr.final td {
            font-weight: bold;
            font-size: 15px;
            background: #f5f5f5;
            border-top: 2px solid #111;
        }

        .notes {
            margin-top: 14px;
            padding: 8px;
            background: #f9f9f9;
            border-right: 3px solid #333;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .cancelled {
            color: #b00020;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>صورتحساب بارگیری واگن</h1>
    <div class="sub">شماره: {{ $billing->billing_number }}</div>
    <div class="status {{ $billing->isCancelled() ? 'cancelled' : '' }}">
        وضعیت: {{ $billing->statusLabel() }}
        @if($billing->isCancelled() && $billing->cancelled_at)
            — تاریخ لغو: {{ \Morilog\Jalali\Jalalian::fromCarbon($billing->cancelled_at)->format('Y/m/d') }}
        @endif
    </div>
</div>

<table class="info">
    <tr>
        <td class="label">صاحب کالا</td>
        <td>{{ $billing->ownerName() }}</td>
        <td class="label">نماینده</td>
        <td>{{ $billing->representativeName() ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">تاریخ صدور</td>
        <td>{{ $billing->billing_date_shamsi }}</td>
        <td class="label">اپراتور</td>
        <td>{{ $billing->operator?->name ?? '-' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th style="width: 6%;">ردیف</th>
        <th style="width: 18%;">شماره واگن</th>
        <th>تعرفه</th>
        <th style="width: 10%;">ظرفیت (تن)</th>
        <th style="width: 10%;">وزن (تن)</th>
        <th style="width: 13%;">هزینه پایه (ریال)</th>
        <th style="width: 11%;">مالیات (ریال)</th>
        <th style="width: 13%;">جمع ردیف (ریال)</th>
    </tr>
    </thead>
    <tbody>
    @forelse($billing->items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->wagon?->wagon_number ?? '-' }}</td>
            <td>{{ $item->tariff_title }}</td>
            <td>{{ $item->tariff_capacity }}</td>
            <td>{{ $item->loaded_weight }}</td>
            <td>{{ number_format($item->base_cost) }}</td>
            <td>{{ number_format($item->tax_amount) }}</td>
            <td>{{ number_format($item->total_amount) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">این صورتحساب لغو شده و ردیفی ندارد.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>جمع هزینه پایه</td>
        <td class="amount">{{ number_format($billing->base_amount) }} ریال</td>
    </tr>
    <tr>
        <td>مالیات ({{ number_format((float) $billing->tax_percentage, 2) }}٪)</td>
        <td class="amount">{{ number_format($billing->tax_amount) }} ریال</td>
    </tr>
    <tr class="final">
        <td>مبلغ قابل پرداخت</td>
        <td class="amount">{{ number_format($billing->total_amount) }} ریال</td>
    </tr>
</table>

@if($billing->notes)
    <div class="notes">
        <strong>یادداشت:</strong> {{ $billing->notes }}
    </div>
@endif

<div class="footer">
    <div>تاریخ چاپ: {{ \App\Support\JalaliDate::today() }}</div>
    <div>این سند به صورت خودکار توسط سامانه مدیریت بارگیری واگن صادر شده است.</div>
</div>

</body>
</html>
