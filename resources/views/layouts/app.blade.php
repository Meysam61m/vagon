<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'سامانه مدیریت بارگیری واگن')</title>

    {{-- همه فایل‌ها به‌صورت محلی سرو می‌شوند؛ برنامه نیازی به اینترنت ندارد --}}
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-rtl/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/vazirmatn.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
@auth
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('cargo.index') }}">سامانه بارگیری واگن</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="منو">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('dashboard')) active @endif"
                           href="{{ route('dashboard') }}">داشبورد مدیر</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('cargo.*')) active @endif"
                       href="{{ route('cargo.index') }}">عملیات بارگیری</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('billing.*')) active @endif"
                       href="{{ route('billing.index') }}">صورتحساب‌ها</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle @if(request()->routeIs('reports.*')) active @endif"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">گزارش‌ها</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item fw-bold text-primary" href="{{ \Illuminate\Support\Facades\Route::has('reports.setad') ? route('reports.setad') : url('/reports/setad') }}">گزارش ستاد</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('reports.daily-loading') }}">گزارش روزانه بارگیری</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.billings') }}">گزارش صورتحساب‌ها</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.wagon-detail') }}">گزارش تفصیلی واگن‌ها</a></li>
                        @if(auth()->user()->isAdmin())
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header text-muted small">گزارش‌های مدیریتی</h6></li>
                            <li><a class="dropdown-item" href="{{ route('reports.financial') }}">گزارش مالی</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.operators') }}">عملکرد اپراتورها</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.owners') }}">عملکرد صاحبان کالا</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.representatives') }}">عملکرد نمایندگان</a></li>
                        @endif
                    </ul>
                </li>
                @if(auth()->user()->isAdmin())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('owners.*', 'representatives.*', 'tariffs.*', 'tax.*', 'users.*')) active @endif"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">مدیریت</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('owners.index') }}">صاحبان کالا</a></li>
                            <li><a class="dropdown-item" href="{{ route('representatives.index') }}">نمایندگان</a></li>
                            <li><a class="dropdown-item" href="{{ route('tariffs.index') }}">تعرفه‌ها</a></li>
                            <li><a class="dropdown-item" href="{{ route('tax.edit') }}">تنظیم مالیات</a></li>
                            <li><a class="dropdown-item" href="{{ route('users.index') }}">کاربران</a></li>
                        </ul>
                    </li>
                @endif
            </ul>

            <a href="{{ route('cargo.create') }}" class="btn btn-primary btn-sm me-3 my-1 my-lg-0 fw-semibold">
                + ثبت درخواست جدید
            </a>

            <span class="navbar-text me-3">
                {{ auth()->user()->name }}
                <span class="badge bg-secondary">{{ auth()->user()->roleLabel() }}</span>
            </span>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">خروج</button>
            </form>
        </div>
    </div>
</nav>
@endauth

<div class="container-fluid px-4 pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>خطا در ورودی‌ها:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap-rtl/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/persian-date.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}?v=2"></script>
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // فعال‌سازی خودکار فیلدهای دارای کلاس jalali-datepicker (اگر پلاگین در دسترس باشد)
        if (window.jQuery && jQuery.fn && jQuery.fn.persianDatepicker) {
            const toEnglishDigits = function (str) {
                return String(str || '')
                    .replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; })
                    .replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; });
            };

            jQuery('.jalali-datepicker').each(function () {
                if (jQuery(this).data('persianDatepicker') || jQuery(this).data('datepicker')) {
                    return;
                }

                // نرمال‌سازی مقدار اولیه به ارقام لاتین و پاک‌سازی سال‌های نامعتبر (مانند ۷۸۴ ناشی از تفسیر میلادی)
                const rawVal = toEnglishDigits(this.value || this.getAttribute('value') || '').trim();
                const match = rawVal.match(/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
                if (match) {
                    const year = parseInt(match[1], 10);
                    if (year < 1300 || year > 1500) {
                        this.value = '';
                        this.removeAttribute('value');
                    } else {
                        const normalized = String(year).padStart(4, '0') + '/'
                            + String(parseInt(match[2], 10)).padStart(2, '0') + '/'
                            + String(parseInt(match[3], 10)).padStart(2, '0');
                        this.value = normalized;
                        this.setAttribute('value', normalized);
                    }
                }

                jQuery(this).persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    initialValue: Boolean(this.value && this.value.trim() !== ''),
                    initialValueType: 'persian',
                    persianDigit: false,
                    observer: true,
                    calendar: {
                        persian: {
                            locale: 'fa',
                            leapYearMode: 'astronomical'
                        }
                    },
                    toolbox: {
                        calendarSwitch: {
                            enabled: false
                        }
                    }
                });
            });
        }
    });
</script>
@stack('scripts')
</body>
</html>
