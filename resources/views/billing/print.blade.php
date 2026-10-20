<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چاپ صورتحساب {{ $billing->billing_number }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="{{ asset('vendor/vazirmatn/Vazirmatn-font-face.css') }}" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            font-size: 13px;
            padding: 24px;
            background: #fff;
            color: #111;
            direction: rtl;
        }

        .print-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 20px;
        }

        .print-toolbar .actions {
            display: flex;
            gap: 8px;
        }

        .btn-print {
            background: #1d4ed8;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-back {
            background: #fff;
            color: #334155;
            border: 1px solid #94a3b8;
            padding: 7px 14px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1.5px solid #111;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-brand img {
            height: 54px;
            width: auto;
            object-fit: contain;
        }

        .header-org-title {
            font-weight: bold;
            font-size: 13px;
            line-height: 1.6;
        }

        .header-center {
            text-align: center;
        }

        .header h1 {
            font-size: 19px;
            margin-bottom: 4px;
        }

        .header .sub {
            font-size: 13px;
            color: #444;
            font-weight: bold;
        }

        .header-meta {
            text-align: left;
            font-size: 12px;
            line-height: 1.7;
        }

        .status {
            margin-top: 4px;
            font-size: 12px;
        }

        table.info {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        table.info td {
            padding: 6px 8px;
            border: 1px solid #ccc;
            text-align: right;
            width: 35%;
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
            border: 1px solid #ccc;
            padding: 6px 8px;
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
            padding: 8px 10px;
            background: #f9f9f9;
            border-right: 3px solid #333;
            line-height: 1.8;
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

        @media print {
            body {
                padding: 0;
            }

            .print-toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <div>
        <strong>پیش‌نمایش چاپ صورتحساب {{ $billing->billing_number }}</strong>
    </div>
    <div class="actions">
        <button type="button" class="btn-print" onclick="window.print()">چاپ صورتحساب</button>
        <a href="{{ route('billing.show', $billing) }}" class="btn-back">بازگشت به صورتحساب</a>
    </div>
</div>

<div class="header">
    <div class="header-brand">
        <img src="{{ asset('images/logo.png') }}" alt="لوگوی شرکت انبارهای عمومی و خدمات گمرکی ایران">
        <div class="header-org-title">
            <div>شرکت انبارهای عمومی و خدمات گمرکی ایران</div>
            <div style="font-weight: normal; color: #444; font-size: 12px;">مجتمع شهید مطهری — عملیات بارگیری واگن</div>
        </div>
    </div>
    <div class="header-center">
        <h1>صورتحساب بارگیری واگن</h1>
        <div class="sub">شماره صورتحساب: {{ $billing->billing_number }}</div>
    </div>
    <div class="header-meta">
        <div><strong>تاریخ صدور:</strong> {{ $billing->billing_date_shamsi }}</div>
        <div class="status {{ $billing->isCancelled() ? 'cancelled' : '' }}">
            <strong>وضعیت:</strong> {{ $billing->statusLabel() }}
            @if($billing->isCancelled() && $billing->cancelled_at)
                — تاریخ لغو: {{ \Morilog\Jalali\Jalalian::fromCarbon($billing->cancelled_at)->format('Y/m/d') }}
            @endif
        </div>
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
        <th style="width: 5%;">ردیف</th>
        <th style="width: 13%;">شماره واگن</th>
        <th style="width: 15%;">شرح کالا</th>
        <th style="width: 18%;">تعرفه</th>
        <th style="width: 8%;">ظرفیت (تن)</th>
        <th style="width: 8%;">وزن (تن)</th>
        <th style="width: 11%;">هزینه پایه (ریال)</th>
        <th style="width: 9%;">مالیات (ریال)</th>
        <th style="width: 13%;">جمع ردیف (ریال)</th>
    </tr>
    </thead>
    <tbody>
    @forelse($billing->items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->wagon?->wagon_number ?? '-' }}</td>
            <td>{{ $item->wagon?->cargo_description ?: '-' }}</td>
            <td>{{ $item->tariff_title }}</td>
            <td>{{ $item->tariff_capacity }}</td>
            <td>{{ $item->loaded_weight }}</td>
            <td>{{ number_format($item->base_cost) }}</td>
            <td>{{ number_format($item->tax_amount) }}</td>
            <td>{{ number_format($item->total_amount) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="9">این صورتحساب لغو شده و ردیفی ندارد.</td>
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

@php
    $editedItems = $billing->items->filter(fn ($i) => $i->wagon?->isOwnerEdited());
@endphp
@if($editedItems->isNotEmpty())
    <div class="notes">
        <strong>ویرایش‌های ثبت‌شده برای واگن‌ها پس از صدور صورتحساب:</strong>
        @foreach($editedItems as $editedItem)
            @php $w = $editedItem->wagon; @endphp
            <div>
                واگن {{ $w->wagon_number }}:
                @if($w->isNumberEdited() && $w->isOwnerChanged())
                    اصلاح شماره واگن از «{{ $w->previous_wagon_number }}» به «{{ $w->wagon_number }}» و تغییر صاحب کالا از «{{ $w->previousOwner?->name ?? '-' }}» به «{{ $w->ownerName() }}»
                @elseif($w->isNumberEdited())
                    اصلاح شماره واگن از «{{ $w->previous_wagon_number }}» به «{{ $w->wagon_number }}»
                @else
                    تغییر صاحب کالا از «{{ $w->previousOwner?->name ?? '-' }}» به «{{ $w->ownerName() }}»
                @endif
                — علت: {{ $w->owner_change_note }}
            </div>
        @endforeach
    </div>
@endif

<div class="footer">
    <div>تاریخ چاپ: {{ \App\Support\JalaliDate::today() }}</div>
    <div>این سند به صورت خودکار توسط سامانه مدیریت بارگیری واگن صادر شده است.</div>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
