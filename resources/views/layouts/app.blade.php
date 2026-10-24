<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'سامانه مدیریت بارگیری واگن') — شرکت انبارهای عمومی و خدمات گمرکی ایران</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    {{-- همه فایل‌ها به‌صورت محلی سرو می‌شوند؛ برنامه نیازی به اینترنت ندارد --}}
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-rtl/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/vazirmatn.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 4px 6px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.85);
            transition: transform 0.15s ease;
        }

        .navbar-brand:hover .brand-logo-wrap {
            transform: scale(1.04);
        }

        .brand-logo-img {
            height: 34px;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .brand-title-main {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
            color: #ffffff;
        }

        .brand-title-sub {
            font-size: 0.7rem;
            color: #9eeaf9;
            font-weight: 400;
            line-height: 1.2;
        }

        .app-org-footer {
            border-top: 1px solid #dee2e6;
            background: #ffffff;
            color: #6c757d;
            font-size: 0.8rem;
            padding: 12px 24px;
            margin-top: 32px;
        }

        .searchable-select-container {
            position: relative;
            width: 100%;
        }

        .searchable-select-native {
            position: absolute !important;
            opacity: 0 !important;
            pointer-events: none !important;
            width: 1px !important;
            height: 1px !important;
            bottom: 0;
            right: 0;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
        }

        .searchable-select-trigger {
            text-align: right;
            cursor: pointer;
            user-select: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            background-color: #fff;
        }

        .searchable-select-dropdown {
            position: absolute;
            top: calc(100% + 2px);
            right: 0;
            left: 0;
            z-index: 1060;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            display: none;
            min-width: 220px;
        }

        .searchable-select-dropdown.show {
            display: block;
        }

        .searchable-select-search-wrap {
            padding: 6px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }

        .searchable-select-options {
            max-height: 230px;
            overflow-y: auto;
            padding: 4px 0;
        }

        .searchable-select-option {
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #212529;
            transition: background-color 0.1s ease;
        }

        .searchable-select-option:hover,
        .searchable-select-option.active {
            background-color: #e9ecef;
        }

        .searchable-select-option.selected {
            background-color: #0d6efd;
            color: #fff;
            font-weight: 600;
        }
    </style>
    @stack('styles')
</head>
<body>
@auth
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2 py-1" href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('cargo.index') }}">
            <span class="brand-logo-wrap">
                <img src="{{ asset('images/logo.png') }}" alt="لوگوی شرکت انبارهای عمومی و خدمات گمرکی ایران" class="brand-logo-img">
            </span>
            <span class="d-flex flex-column">
                <span class="brand-title-main">سامانه بارگیری واگن</span>
                <span class="brand-title-sub">شرکت انبارهای عمومی و خدمات گمرکی ایران</span>
            </span>
        </a>
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
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('calculator.*')) active @endif"
                       href="{{ \Illuminate\Support\Facades\Route::has('calculator.index') ? route('calculator.index') : url('/calculator') }}">محاسبه‌گر تعرفه</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('owners.*')) active @endif"
                       href="{{ route('owners.index') }}">صاحبان کالا</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('representatives.*')) active @endif"
                       href="{{ route('representatives.index') }}">نمایندگان</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle @if(request()->routeIs('reports.*')) active @endif"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">گزارش‌ها</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item fw-bold text-warning-emphasis" href="{{ \Illuminate\Support\Facades\Route::has('reports.pending-loading') ? route('reports.pending-loading') : url('/reports/pending-loading') }}">گزارش در انتظار بارگیری</a></li>
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

@auth
<footer class="app-org-footer no-print">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="لوگو" style="height: 24px; width: auto;">
            <span><strong>شرکت انبارهای عمومی و خدمات گمرکی ایران</strong> — مجتمع شهید مطهری (سامانه مدیریت بارگیری واگن)</span>
        </div>
        <div class="text-muted small">
            تاریخ امروز: {{ \App\Support\JalaliDate::today() }}
        </div>
    </div>
</footer>
@endauth

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

        // تبدیل خودکار فیلدهای انتخاب صاحب کالا و نماینده به دراپ‌داون قابل جستجو (Searchable Select)
        const normalizePersian = function (str) {
            return String(str || '')
                .replace(/ي/g, 'ی')
                .replace(/ك/g, 'ک')
                .replace(/\u200c/g, ' ')
                .replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; })
                .replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; })
                .toLowerCase()
                .trim();
        };

        const nativeSelectValueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');

        function initSearchableSelect(select) {
            if (!select || select.dataset.searchableInitialized === '1') {
                return;
            }
            select.dataset.searchableInitialized = '1';

            const container = document.createElement('div');
            container.className = 'searchable-select-container';
            if (select.style.minWidth) {
                container.style.minWidth = select.style.minWidth;
            }
            if (select.style.width) {
                container.style.width = select.style.width;
            }

            const trigger = document.createElement('div');
            trigger.tabIndex = 0;
            trigger.className = 'form-select searchable-select-trigger';
            if (select.classList.contains('form-select-sm')) {
                trigger.classList.add('form-select-sm');
            }
            if (select.classList.contains('py-0')) {
                trigger.classList.add('py-0');
            }
            if (select.classList.contains('is-invalid')) {
                trigger.classList.add('is-invalid');
            }
            if (select.style.height) {
                trigger.style.height = select.style.height;
            }
            if (select.style.fontSize) {
                trigger.style.fontSize = select.style.fontSize;
            }

            const dropdown = document.createElement('div');
            dropdown.className = 'searchable-select-dropdown';

            const searchWrap = document.createElement('div');
            searchWrap.className = 'searchable-select-search-wrap';

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'form-control form-control-sm searchable-select-input';
            searchInput.placeholder = 'جستجو با تایپ نام…';
            searchInput.autocomplete = 'off';
            searchWrap.appendChild(searchInput);

            const optionsList = document.createElement('div');
            optionsList.className = 'searchable-select-options';

            dropdown.appendChild(searchWrap);
            dropdown.appendChild(optionsList);

            select.parentNode.insertBefore(container, select);
            container.appendChild(select);
            container.appendChild(trigger);
            container.appendChild(dropdown);
            select.classList.add('searchable-select-native');
            select.tabIndex = -1;

            function syncTriggerLabel() {
                const selectedOpt = select.options[select.selectedIndex];
                trigger.textContent = selectedOpt ? selectedOpt.textContent.trim() : 'انتخاب کنید…';
                trigger.title = trigger.textContent;
            }

            function renderOptions(filterQuery) {
                const q = normalizePersian(filterQuery || '');
                optionsList.innerHTML = '';
                let visibleCount = 0;

                Array.from(select.options).forEach(function (opt) {
                    if (opt.hidden) {
                        return;
                    }
                    const text = opt.textContent.trim();
                    if (q !== '' && !normalizePersian(text).includes(q)) {
                        return;
                    }

                    const item = document.createElement('div');
                    item.className = 'searchable-select-option';
                    if (String(opt.value) === String(select.value)) {
                        item.classList.add('selected', 'active');
                    } else if (visibleCount === 0 && q !== '') {
                        item.classList.add('active');
                    }
                    item.dataset.value = opt.value;
                    item.textContent = text;

                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selectValue(opt.value);
                    });

                    optionsList.appendChild(item);
                    visibleCount++;
                });

                if (visibleCount === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted small p-2 text-center';
                    empty.textContent = 'موردی یافت نشد';
                    optionsList.appendChild(empty);
                }
            }

            function selectValue(val) {
                const changed = String(select.value) !== String(val);
                if (nativeSelectValueDesc && nativeSelectValueDesc.set) {
                    nativeSelectValueDesc.set.call(select, val);
                } else {
                    select.value = val;
                }
                syncTriggerLabel();
                closeDropdown();
                if (changed) {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            function openDropdown() {
                document.querySelectorAll('.searchable-select-dropdown.show').forEach(function (el) {
                    if (el !== dropdown) {
                        el.classList.remove('show');
                    }
                });
                searchInput.value = '';
                renderOptions('');
                dropdown.classList.add('show');
                setTimeout(function () {
                    searchInput.focus();
                    const selectedItem = optionsList.querySelector('.searchable-select-option.selected');
                    if (selectedItem) {
                        selectedItem.scrollIntoView({ block: 'nearest' });
                    }
                }, 10);
            }

            function closeDropdown() {
                dropdown.classList.remove('show');
            }

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                if (dropdown.classList.contains('show')) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });

            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    openDropdown();
                }
            });

            searchInput.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            searchInput.addEventListener('input', function () {
                renderOptions(searchInput.value);
            });

            searchInput.addEventListener('keydown', function (e) {
                const items = Array.from(optionsList.querySelectorAll('.searchable-select-option'));
                let activeIdx = items.findIndex(function (el) { return el.classList.contains('active'); });

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (items.length === 0) return;
                    items.forEach(function (el) { el.classList.remove('active'); });
                    activeIdx = (activeIdx + 1) % items.length;
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (items.length === 0) return;
                    items.forEach(function (el) { el.classList.remove('active'); });
                    activeIdx = activeIdx <= 0 ? items.length - 1 : activeIdx - 1;
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const target = activeIdx >= 0 ? items[activeIdx] : items[0];
                    if (target) {
                        selectValue(target.dataset.value);
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    closeDropdown();
                    trigger.focus();
                }
            });

            // رهگیری تغییرات برنامه‌نویسی روی select.value و گزینه‌ها
            if (nativeSelectValueDesc && nativeSelectValueDesc.get && nativeSelectValueDesc.set) {
                Object.defineProperty(select, 'value', {
                    configurable: true,
                    get: function () {
                        return nativeSelectValueDesc.get.call(this);
                    },
                    set: function (v) {
                        nativeSelectValueDesc.set.call(this, v);
                        syncTriggerLabel();
                    }
                });
            }

            select.addEventListener('change', syncTriggerLabel);

            const observer = new MutationObserver(function () {
                syncTriggerLabel();
                if (dropdown.classList.contains('show')) {
                    renderOptions(searchInput.value);
                }
            });
            observer.observe(select, { childList: true, subtree: true, attributes: true });

            syncTriggerLabel();
        }

        document.querySelectorAll('select[name="owner_id"], select[name="representative_id"], select[data-searchable="true"]').forEach(initSearchableSelect);

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.searchable-select-container')) {
                document.querySelectorAll('.searchable-select-dropdown.show').forEach(function (el) {
                    el.classList.remove('show');
                });
            }
        });

        // جستجوی لحظه‌ای چک‌باکس‌های صاحبان کالا در فرم افزودن/ویرایش نماینده
        document.querySelectorAll('[data-owner-checkbox-search]').forEach(function (searchBox) {
            const containerSelector = searchBox.getAttribute('data-owner-checkbox-search');
            const listContainer = containerSelector ? document.querySelector(containerSelector) : null;
            if (!listContainer) return;

            searchBox.addEventListener('input', function () {
                const q = normalizePersian(searchBox.value);
                listContainer.querySelectorAll('[data-owner-checkbox-item]').forEach(function (item) {
                    const labelText = normalizePersian(item.textContent || '');
                    item.style.display = (q === '' || labelText.includes(q)) ? '' : 'none';
                });
            });
        });

        // محاسبه‌گر مستقل تعرفه و تناژ (بدون ثبت در دیتابیس و با امکان ورود تناژ بیشتر از ظرفیت)
        const fmtMoney = function (n) {
            return Math.round(n || 0).toLocaleString('en-US');
        };

        document.querySelectorAll('[data-tariff-calculator]').forEach(function (calcBox) {
            const taxPct = parseFloat(calcBox.dataset.tax || '10');
            const tariffSelect = calcBox.querySelector('.calc-tariff-select');
            const tonnageInput = calcBox.querySelector('.calc-tonnage-input');
            const wagonCountInput = calcBox.querySelector('.calc-wagon-count-input');
            const resetBtn = calcBox.querySelector('.calc-reset-btn');
            const resultBox = calcBox.querySelector('.calc-result-box');
            const overCapBadge = calcBox.querySelector('.calc-over-capacity-badge');

            const outCap = calcBox.querySelector('.calc-out-capacity');
            const outEquip = calcBox.querySelector('.calc-out-equipment');
            const outBooklet = calcBox.querySelector('.calc-out-booklet-rate');
            const outPerTon = calcBox.querySelector('.calc-out-per-ton');
            const outBase = calcBox.querySelector('.calc-out-base');
            const outTax = calcBox.querySelector('.calc-out-tax');
            const outTotal = calcBox.querySelector('.calc-out-total');

            function updateCalculation(fromTariffChange) {
                if (!tariffSelect || !tonnageInput || !resultBox) return;

                const opt = tariffSelect.options[tariffSelect.selectedIndex];
                if (!opt || !opt.value) {
                    resultBox.classList.add('d-none');
                    return;
                }

                const rate = parseFloat(opt.dataset.rate || '0');
                const cap = parseFloat(opt.dataset.capacity || '65');
                const equip = opt.dataset.equipment || '';

                if (fromTariffChange && (!tonnageInput.value || tonnageInput.value.trim() === '')) {
                    tonnageInput.value = String(cap);
                }

                const tonnage = parseFloat(tonnageInput.value || '0');
                const wagonCount = Math.max(1, parseInt(wagonCountInput ? wagonCountInput.value : '1', 10) || 1);

                if (isNaN(tonnage) || tonnage <= 0 || cap <= 0) {
                    resultBox.classList.add('d-none');
                    return;
                }

                const perTonRate = rate / cap;
                const singleBase = Math.round((tonnage / cap) * rate);
                const totalBase = singleBase * wagonCount;
                const totalTax = Math.round(totalBase * (taxPct / 100));
                const grandTotal = totalBase + totalTax;

                if (outCap) outCap.textContent = String(cap);
                if (outEquip) outEquip.textContent = equip ? ('(' + equip + ')') : '';
                if (outBooklet) outBooklet.textContent = fmtMoney(rate);
                if (outPerTon) outPerTon.textContent = fmtMoney(perTonRate);
                if (outBase) outBase.textContent = fmtMoney(totalBase);
                if (outTax) outTax.textContent = fmtMoney(totalTax);
                if (outTotal) outTotal.textContent = fmtMoney(grandTotal);

                if (overCapBadge) {
                    if (tonnage > cap) {
                        overCapBadge.textContent = 'توجه: تناژ واردشده (' + tonnage + ' تن) بیشتر از ظرفیت پایه تعرفه (' + cap + ' تن) است و مبلغ به نسبت تناژ واردشده محاسبه گردید.';
                        overCapBadge.classList.remove('d-none');
                    } else {
                        overCapBadge.classList.add('d-none');
                        overCapBadge.textContent = '';
                    }
                }

                resultBox.classList.remove('d-none');
            }

            if (tariffSelect) {
                tariffSelect.addEventListener('change', function () {
                    updateCalculation(true);
                });
            }
            if (tonnageInput) {
                tonnageInput.addEventListener('input', function () {
                    updateCalculation(false);
                });
            }
            if (wagonCountInput) {
                wagonCountInput.addEventListener('input', function () {
                    updateCalculation(false);
                });
            }
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    if (tariffSelect) tariffSelect.value = '';
                    if (tonnageInput) tonnageInput.value = '';
                    if (wagonCountInput) wagonCountInput.value = '1';
                    if (resultBox) resultBox.classList.add('d-none');
                });
            }
        });

        // تمدید خودکار نشست کاری (Session Keep-Alive) و به‌روزرسانی توکن‌های CSRF در فرم‌های باز
        const keepAliveUrl = @json(url('/session/keep-alive'));
        let lastKeepAliveAt = Date.now();

        function refreshSessionHeartbeat() {
            lastKeepAliveAt = Date.now();
            fetch(keepAliveUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            })
                .then(function (res) {
                    return res.ok ? res.json() : null;
                })
                .then(function (data) {
                    if (data && data.token) {
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) {
                            meta.setAttribute('content', data.token);
                        }
                        document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                            input.value = data.token;
                        });
                    }
                })
                .catch(function () {
                    // نادیده گرفتن خطاهای موقت شبکه
                });
        }

        // ارسال پینگ تمدید نشست هر ۴ دقیقه یک‌بار
        setInterval(refreshSessionHeartbeat, 4 * 60 * 1000);

        // اگر تب مرورگر برای مدتی در پس‌زمینه بوده و کاربر دوباره به آن برگردد، بلافاصله توکن را تازه کن
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && (Date.now() - lastKeepAliveAt) > 60 * 1000) {
                refreshSessionHeartbeat();
            }
        });
    });
</script>
@stack('scripts')
</body>
</html>
