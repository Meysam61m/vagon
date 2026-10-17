# بررسی پروژه «سامانه مدیریت بارگیری واگن» (`wagon-system.rar`)

**تاریخ بررسی:** ۲۰۲۶/۱۰/۱۷ · **روش:** باز کردن آرشیو RAR، خواندن ۱۰۶ فایل (۱۱ کنترلر، ۹ مدل، ۳۴ نمای Blade) و بررسی استاتیک کد + لاگ موجود در آرشیو.
PHP در محیط بررسی نصب نبود، بنابراین پروژه اجرا نشده؛ اما `storage/logs/laravel.log` آرشیو، خطاهای واقعی اجرای قبلی روی XAMPP را نشان می‌دهد و به‌عنوان شاهد استفاده شده است.

---

## وضعیت اصلاحات (نسخه بازنویسی‌شده)

این بخش بعد از بازنویسی پروژه اضافه شده است. کد در همین مخزن با **PHP 8.4** (runtime WASM بدون Composer) اجرا شد: migrationها و seederها اجرا شدند، ۴۰+ درخواست HTTP واقعی (ورود، CRUD مدیریت، چرخه بارگیری/صورتحساب/لغو، همه گزارش‌ها، کنترل دسترسی) پاسخ درست دادند، ۱۰۵ فایل PHP و ۳۳ فایل Blade بدون خطای نحوی/کامپایل بودند. محیط اجرای آزمایشی به MySQL دسترسی نداشت، بنابراین بوت با SQLite (حالت جایگزین) انجام شد و مسیرهای MySQL (تشخیص خطای اتصال، ساخت دیتابیس، جدول‌ها) به‌صورت استاتیک و در `diagnose.php`/`index.php` پیاده و بررسی شدند. تولید PDF در آن محیط ممکن نبود چون افزونه `dom` در runtime آزمایشی وجود ندارد؛ صحت HTML فاکتور (RTL، `@font-face`، مسیر `file:///`) بررسی شد.

| بند | وضعیت | توضیح کوتاه |
|---|---|---|
| ۱.۱ روت‌ها | ✅ اصلاح شد | `routes/web.php` و `console.php` کامل؛ همه ۵۲ نام‌روت استفاده‌شده در نماها تعریف شده‌اند |
| ۱.۲ migration/seeder | ✅ اصلاح شد | ۱۲ migration (+ sessions/cache/jobs) و ۴ seeder؛ دیتابیس اصلی MySQL است و migrationها بدون تغییر روی هر دو موتور اجرا می‌شوند |
| ۱.۳ entry point/public | ✅ اصلاح شد | `public/index.php`، `.htaccess`، `css/app.css` و دارایی‌های آفلاین |
| ۱.۴ env/timezone | ✅ اصلاح شد | `.env` حذف شد؛ `.env.example` امن؛ `APP_TIMEZONE` واقعاً خوانده می‌شود |
| ۱.۵ require دستی | ✅ اصلاح شد | همه requireهای دستی و نگاشت PSR-4 غیرضروری حذف شد؛ فقط autoload Composer |
| ۲.۱ دوبار صورتحساب | ✅ اصلاح شد | آرایه تودرتوی `wagons[]`، قفل ردیف‌ها، بررسی مالکیت/تسویه/بارگیری/ظرفیت، ایندکس unique روی `billing_items.wagon_id` |
| ۲.۲ شماره تکراری | ✅ اصلاح شد | `DocumentNumber` با `withTrashed`، unique index و retry؛ شماره شمسی روزانه |
| ۲.۳ جمع گزارش | ✅ اصلاح شد | aggregate جدا روی همان فیلتر (مستقل از صفحه‌بندی) و جدا کردن لغوشده‌ها |
| ۲.۴ تاریخ شمسی | ✅ اصلاح شد | `JalaliDate` (نرمال‌سازی ارقام/جداکننده، اعتبارسنجی، خطای فارسی)؛ حذف fallback خاموش؛ نمایش `warning` |
| ۲.۵ فونت/RTL PDF | ✅ اصلاح شد | `Vazirmatn` در config و `@font-face`، `dir="rtl"`، `isPhpEnabled=false`، مسیر سازگار ویندوز/لینوکس |
| ۲.۶ چک‌باکس فاکتور | ✅ اصلاح شد | چک‌باکس از حلقه disable مستثنا شد؛ انتخاب مجدد ممکن است |
| ۲.۷ حذف کاربر | ✅ اصلاح شد | soft delete + محافظت از آخرین ادمین فعال و حساب خود |
| ۳.۱ فیلترهای ترکیبی | ✅ اصلاح شد | جستجوها داخل closure گروه‌بندی شدند (تعرفه/کاربر/مالک/نماینده) |
| ۳.۲ صفحه‌بندی | ✅ اصلاح شد | `Paginator::useBootstrapFive()` |
| ۳.۳ وابستگی فرانت | ✅ اصلاح شد | Bootstrap 5.3.8 RTL رسمی، jQuery 3.7.1، pin persian-date، حذف Vite/Tailwind بی‌استفاده |
| ۳.۴ پیام warning | ✅ اصلاح شد | success/warning/info و خطاهای اعتبارسنجی در layout |
| ۳.۵ وضعیت‌ها | ✅ اصلاح شد | `pending/loaded/billed/cancelled`، فرم ثبت بارگیری، لغو و آزادسازی |
| ۳.۶ گزارش‌ها | ✅ اصلاح شد | مالیات per-item، تاریخ شمسی، گروه‌بندی بر اساس id، نمودار بارگیری بر اساس `wagons.loaded_on` |
| ۳.۷ فیلترهای مرده | ✅ اصلاح شد | فرم فیلتر در فهرست بارگیری و صورتحساب‌ها |
| ۳.۸ حفظ فرم | ✅ اصلاح شد | `old()` برای واگن‌ها/تعرفه/وزن/تاریخ؛ تاریخ پیش‌فرض امروز |
| ۳.۹ پیام فارسی | ✅ اصلاح شد | `lang/fa/{validation,auth,passwords,pagination}.php` |
| ۳.۱۰ وضعیت انگلیسی | ✅ اصلاح شد | `STATUS_LABELS` و `statusLabel()` در مدل‌ها |
| ۴.۱ تراکنش مالیات | ✅ اصلاح شد | به‌روزرسانی در `DB::transaction` با lock |
| ۴.۲ شماره‌گذاری | ✅ اصلاح شد | همان `DocumentNumber` |
| ۴.۳ snapshot هویتی | ✅ اصلاح شد | نام صاحب کالا/نماینده در فاکتور؛ عنوان/نرخ/ظرفیت تعرفه در ردیف‌ها (کد ملی ذخیره نمی‌شود) |
| ۴.۴ وزن > ظرفیت | ✅ اصلاح شد | اعتبارسنجی در ثبت بارگیری و صدور فاکتور |
| ۴.۵ واگن تکراری | ✅ اصلاح شد | `distinct` + unique(cargo_request_id, wagon_number) |
| ۴.۶ is_active کاربر | ✅ اصلاح شد | در `users.store` صریحاً true |
| ۴.۷ فیلتر نماینده | ✅ اصلاح شد | فیلتر وضعیت + جستجو |
| ۴.۸ فیلد category | ✅ اصلاح شد | برچسب‌های دسته و نمایش در فهرست/فرم تعرفه |
| ۴.۹ حذف مالک/نماینده | ✅ اصلاح شد | مسیرهای destroy با محافظت سابقه + استفاده از snapshot/`?->` |
| ۴.۱۰ welcome | ✅ اصلاح شد | حذف و redirect به داشبورد |
| ۴.۱۱ ارقام فارسی | ⏸ خارج از دامنه | خروجی با ارقام لاتین و جداکننده هزارگان؛ در README مستند شد |
| ۴.۱۲ APP_NAME | ✅ اصلاح شد | نام فارسی سامانه در `.env.example` و layout |
| ۴.۱۳ فایل‌های تولیدی | ✅ اصلاح شد | `.env`، لاگ، cache و view کامپایل‌شده در `.gitignore` و خارج از مخزن |
| ۴.۱۴ composer.lock | ✅ اصلاح شد | lock حفظ شد و content-hash با الگوریتم Composer بازمحاسبه شد |
| ۴.۱۵ soft delete کاربر | ✅ اصلاح شد | همان بند ۲.۷ |
| ۴.۱۶ throttle ورود | ✅ اصلاح شد | `throttle:5,1` + محدودیت دستی در کنترلر |
| ۴.۱۷ رمز پیش‌فرض | ✅ اصلاح شد | seeder رمز تصادفی می‌سازد؛ `admin:reset-password` برای بازیابی |
| ۴.۱۸ Audit Log | ⏸ به تعویق افتاد | خارج از دامنه این اصلاح؛ فقط تاریخچه مالیات و snapshotها موجود است (در README مستند شد) |
| ۴.۱۹ پول با float | ⚠️ پذیرفته شد | محاسبه با `round` و ذخیره integer ریالی؛ برای اعشار پولی باید ×۱۰۰ شود (مستند شد) |
| ۴.۲۰ اسکریپت در همه صفحات | ⚠️ پذیرفته شد | دارایی‌ها محلی و کوچک‌اند؛ بارگذاری شرطی لازم نیست |

**دیتابیس:** دیتابیس اصلی از SQLite به **MySQL/MariaDB** تغییر کرد (`utf8mb4_unicode_ci`). فایل `database/create_database.sql` برای ساخت دیتابیس، `diagnose.php` برای بررسی اتصال و جدول‌ها، و `public/index.php` برای پیام‌های دقیق خطاهای MySQL (دیتابیس ناموجود، Access denied، Connection refused، نبود `pdo_mysql`) اضافه شد. SQLite فقط برای تست‌های خودکار (in-memory) باقی ماند. همچنین ستون `cargo_description` (شرح کالا) به جدول `wagons` اضافه شد؛ در فرم ثبت درخواست برای هر واگن **اجباری** است، در فرم بارگیری قابل ویرایش است (و برای واگنی که تازه بارگیری می‌شود هم اجباری است)، در فاکتور و نسخه چاپی نمایش داده می‌شود، در گزارش تفصیلی واگن‌ها، گزارش روزانه بارگیری و گزارش صورتحساب‌ها درج می‌شود (در گزارش‌های عملکردی/مالی که سطرها تجمیعی هستند ستون کالا معنا ندارد) و در جست‌وجوی درخواست‌ها هم لحاظ شده؛ در فرم ثبت، شرح کالای هر ردیف از ردیف قبلی پیشنهاد داده می‌شود (برای واگن‌های هم‌کالا). ستون دیتابیس همچنان nullable است تا داده‌های قدیمی بدون خطا بمانند؛ اجبار در لایه اعتبارسنجی اعمال می‌شود. در نسخه بعد، تشخیص خطای `Access denied` کامل‌تر شد: هر دو مسیر `localhost` و `127.0.0.1` خودکار امتحان می‌شود، نام کاربری/مسیر/طول رمز خوانده‌شده نمایش داده می‌شود، خطای ۱۰۴۴ (نبود Privileges) جداگانه توضیح داده می‌شود و اگر `bootstrap/cache/config.php` وجود داشته باشد هشدار `config:clear` داده می‌شود. خواندن `.env` هم با قواعد کوتیشن/کامنت/`export` هم‌راستا با Laravel شد.

**نکته درباره تست:** فایل‌های PHPUnit نوشته شده‌اند اما در این محیط Composer و PHPUnit نصب نیست و افزونه `dom` برای PHPUnit وجود ندارد؛ بنابراین `php artisan test` اینجا اجرا نشد. رفتار معادل با اجرای واقعی HTTP در runtime PHP 8.4 بررسی شد. روی سرور با `composer install` و PHP کامل، `php artisan test` باید اجرا شود.

---

## حکم کلی

به‌عنوان **اسکلت معماری**، پروژه تمیز و قابل‌قبول است: ماژولار، با تراکنش دیتابیس، snapshot تعرفه/مالیات و جداسازی تاریخ میلادی/شمسی.
اما به‌عنوان **پروژه قابل اجرا**، این آرشیو ناقص است: `routes/`، `database/` (migration و seeder) و `public/` اصلاً وجود ندارند. حتی با `composer install` هم برنامه بالا نمی‌آید. علاوه بر آن چند باگ مالی/داده‌ای جدی دارد که باید قبل از استفاده واقعی رفع شود.

| حوزه | وضعیت |
|---|---|
| معماری و خوانایی | خوب |
| کامل بودن برای اجرا | ❌ ناقص (فایل‌های کلیدی غایب) |
| صحت منطق مالی | ⚠️ ریسک دوباره‌صورتحساب و شماره تکراری |
| امنیت | ⚠️ `.env` و رمزهای پیش‌فرض منتشر شده‌اند |
| UI/PDF فارسی | ⚠️ وابستگی‌های ناسازگار و فونت PDF اشتباه |
| مستندات | ⚠️ README با محتوای آرشیو نمی‌خواند |

---

## ۰) داخل آرشیو چیست؟

```
app/Http/Controllers   ۱۱ فایل   (Auth, User, Owner, Representative, Tariff, Tax,
                                  Dashboard, Cargo, Billing, Report, Controller)
app/Http/Middleware    CheckRole
app/Models             ۹ مدل
resources/views        ۳۴ فایل Blade
config/                ۱۳ فایل (از جمله dompdf و jalali)
composer.json / composer.lock / package.json / package-lock.json
bootstrap/ / storage/ / .env / laravel.log / فونت Vazirmatn
```

**غایب:** `routes/`، `database/`، `public/`، `tests/`، `vendor/`، `lang/`، فایل‌های آفلاین `public/vendor/*`.

---

## ۱) مسدودکننده‌ها — برنامه اجرا نمی‌شود (P0)

### ۱.۱ فایل روت‌ها وجود ندارد
`bootstrap/app.php:12-13` به `routes/web.php` و `routes/console.php` اشاره می‌کند، ولی پوشه `routes/` در آرشیو نیست. طبق ساختار Laravel، این فایل‌ها هنگام ثبت روت‌ها `require` می‌شوند و نبودشان خطای Fatal می‌دهد. تمام ۵۰ نام‌روت استفاده‌شده در نماها (فهرست کامل در پیوست ب) باید در `routes/web.php` ساخته شوند.
**اثر:** هیچ صفحه‌ای بالا نمی‌آید؛ حتی `php artisan` هم ممکن است در boot شکست بخورد.

### ۱.۲ migration و seeder وجود ندارد
README می‌گوید «تمام migrationها و seeders» در پروژه هست، اما پوشه `database/` اصلاً وجود ندارد. در نتیجه:
- جدول‌های `users`, `owners`, `representatives`, `tariffs`, `tax_settings`, `cargo_requests`, `wagons`, `billings`, `billing_items`, `owner_representative` ساخته نمی‌شوند.
- `SESSION_DRIVER=database` و `CACHE_STORE=database` و `QUEUE_CONNECTION=database` در `.env` هستند، پس جدول‌های `sessions`, `cache`, `jobs` هم لازم‌اند و نیستند.
- کاربر admin/operator و تعرفه‌های پایه ساخته نمی‌شوند؛ ورود غیرممکن است.

### ۱.۳ entry point عمومی وجود ندارد
`public/index.php`, `public/.htaccess` و دارایی‌های `public/vendor/*` و `public/css/app.css` نیستند. با وب‌سرور نمی‌شود سرو کرد. مسیرهای `asset('vendor/...')` در `layouts/app.blade.php:9-12` و `80-84` همگی ۴۰۴ می‌شوند.

### ۱.۴ `.env` نامناسب و حاوی کلید است
- `.env:3` شامل `APP_KEY` واقعی است (مقدارش را عمداً اینجا تکرار نمی‌کنم) و آرشیو در یک ریپوی عمومی GitHub است → کلید باید **بازتولید شود**.
- `.env` با `.env.example` متفاوت است: `.env` حالت توسعه لاراول است (`APP_ENV=local`, `APP_DEBUG=true`, `APP_LOCALE=en`, session/cache/queue دیتابیسی)، در حالی که `.env.example` برای استقرار تنظیم شده (`production`, `Asia/Tehran`, `fa`, session/cache فایلی).
- `config/app.php:68` مقدار `timezone` را **هاردکد `UTC`** کرده و `APP_TIMEZONE` را نمی‌خواند؛ بنابراین `Asia/Tehran` در `.env.example` بی‌اثر است. این باعث می‌شود «امروز»، نمودارهای روزانه و شماره‌گذاری فاکتور بین ۰۰:۰۰ تا ۰۳:۳۰ بامداد تهران، روز قبل حساب شوند.
- `APP_DEBUG=true` در یک سیستم مالی، در صورت بروز خطا stack trace و مسیرها را به کاربر نشان می‌دهد.

### ۱.۵ هک‌های بارگذاری دستی کلاس
- `bootstrap/app.php:3` فایل `vendor/morilog/jalali/src/helpers.php` را دستی require می‌کند؛ قبل از نصب vendor خطای Fatal می‌دهد و لایه‌بندی autoload را می‌شکند.
- `app/Http/Controllers/DashboardController.php:6` کلاس `Jalalian.php` را تنها (بدون Converter و helperها) require می‌کند؛ همین کار در لاگ آرشیو دقیقاً خطای `Trait "Morilog\Jalali\Converter" not found` و `Class "Morilog\Jalali\Jalalian" not found` را تولید کرده است.
- `composer.json:24` نگاشت `Morilog\Jalali\` به `vendor/...` هم غیرضروری و اشتباه است؛ خود پکیج autoload دارد.

هر سه باید حذف شوند و فقط autoload کامپوزر مبنا باشد.

---

## ۲) باگ‌های مالی و داده‌ای (P1)

### ۲.۱ امکان دوبار صورتحساب‌شدن یک واگن ⛔
در `BillingController::store` (`app/Http/Controllers/BillingController.php:55-60`) فقط `exists:wagons,id` چک می‌شود. هیچ‌کدام از این‌ها بررسی نمی‌شوند:
- `is_settled = false` بودن واگن،
- تعلق واگن به `owner_id` انتخاب‌شده،
- تکراری نبودن `wagon_ids`
- برابر بودن طول آرایه‌های `wagon_ids` / `tariff_ids` / `loaded_weights`.

صفحه ساخت فاکتور فقط واگن‌های تسویه‌نشده را نشان می‌دهد، اما endpoint POST هر شناسه‌ای را می‌پذیرد. با یک درخواست دستی (یا صفحه stale) می‌توان واگن تسویه‌شده یا واگن صاحب‌کالای دیگر را دوباره صورتحساب کرد. اگر طول آرایه‌ها فرق کند، `$data['tariff_ids'][$index]` خطای `Undefined array key` می‌دهد (۵۰۰).

**اصلاح پیشنهادی:**
```php
'wagon_ids'   => ['required', 'array', 'min:1'],
'wagon_ids.*' => ['required', 'distinct', 'exists:wagons,id'],

// داخل تراکنش، قبل از محاسبه:
$ids = $data['wagon_ids'];
$valid = Wagon::whereIn('id', $ids)
    ->where('is_settled', false)
    ->whereHas('cargoRequest', fn ($q) => $q->where('owner_id', $data['owner_id']))
    ->lockForUpdate()
    ->count();

if ($valid !== count($ids) || count($data['tariff_ids']) !== count($ids)
    || count($data['loaded_weights']) !== count($ids)) {
    throw ValidationException::withMessages(['wagon_ids' => 'واگن‌های انتخاب‌شده معتبر یا تسویه‌نشده نیستند.']);
}
```
ساختار بهتر: به‌جای سه آرایه موازی، یک آرایه تودرتو `wagons[] => [id, tariff_id, weight]` بفرستید تا ناهم‌ترازی اصلاً ممکن نباشد.
همچنین روی `billing_items.wagon_id` ایندکس یکتا بگذارید.

### ۲.۲ شماره فاکتور/درخواست تکراری می‌شود
`app/Models/Billing.php:50-56` و `app/Models/CargoRequest.php:43-49`:
```php
$count = static::whereDate('created_at', today())->count() + 1;
return sprintf('INV-%s-%04d', $today, $count);
```
مشکلات:
- **حذف نرم:** `Billing` از SoftDeletes استفاده می‌کند؛ با حذف یک فاکتور، `count()` کم می‌شود و شماره بعدی دوباره همان شماره قبلی می‌شود.
- **همزمانی:** دو اپراتور همزمان دو شماره یکسان می‌گیرند.
- **بازه روز:** شماره بر اساس تاریخ شمسی است ولی count بر اساس `created_at` میلادی/UTC.

**اصلاح:** جدول sequence روزانه با `lockForUpdate()`، یا `withTrashed()->where('billing_number','like',"INV-$today-%")->max('billing_number')` داخل تراکنش + ایندکس `unique` روی `billing_number` و retry در صورت تصادم. همین برای `request_number`.

### ۲.۳ جمع گزارش‌ها فقط صفحه جاری را حساب می‌کند
`app/Http/Controllers/ReportController.php:56-58`:
```php
'base' => (clone $billings)->getCollection()->sum('base_amount'),
```
`$billings` یک Paginator است؛ `getCollection()` فقط ۲۰ ردیف همان صفحه را می‌دهد. یعنی «جمع کل» گزارش صورتحساب‌ها با صفحه‌بندی تغییر می‌کند و **کمتر از واقعیت** است. باید با یک کوئری aggregate جدا روی همان فیلتر محاسبه شود:
```php
$baseQuery = Billing::whereBetween('billing_date', [$from, $to]);
$totals = [
    'base'  => (clone $baseQuery)->sum('base_amount'),
    'tax'   => (clone $baseQuery)->sum('tax_amount'),
    'total' => (clone $baseQuery)->sum('total_amount'),
];
```

### ۲.۴ تاریخ شمسی بدون اعتبارسنجی و با fallback خاموش
- `ReportController::dateRange` (`ReportController.php:17-27`) و `DashboardController::resolveDateRange` (`DashboardController.php:56-62`) هر ورودی‌ای را مستقیم به `Jalalian::fromFormat('Y/m/d', ...)` می‌دهند. ورودی نامعتبر یا رقم فارسی → ۵۰۰.
- در `BillingController::store:69-76` و `CargoController::store:47-61` خطا گرفته می‌شود، اما **تاریخ امروز جایگزین** می‌شود و فقط یک `session('warning')` می‌گذارد که در `layouts/app.blade.php:64-72` اصلاً نمایش داده نمی‌شود (فقط success و errors رندر می‌شوند). یعنی کاربر تاریخ اشتباه وارد می‌کند و فاکتور با تاریخ دیگر صادر می‌شود، بدون هیچ پیامی.

لاگ آرشیو شاهد واقعی این باگ است: `Undefined array key "year" at .../CalendarUtils.php:743` از `BillingController.php(65)`؛ ورودی به‌صورت رشته‌ای با ارقام فارسی به پارسر رسیده بود.

**اصلاح:** یک helper مشترک برای نرمال‌سازی ارقام فارسی/عربی، یکسان‌سازی جداکننده‌ها، `preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/')` و `try/catch` که به‌جای fallback، خطای اعتبارسنجی فارسی برگرداند. تاریخ ذخیره‌شده در `*_shamsi` هم باید همان مقدار نرمال‌شده باشد، نه ورودی خام.

### ۲.۵ فونت PDF فارسی اشتباه است + PHP فعال
- `config/dompdf.php:93` فونت پیش‌فرض را `Vazirmatn` گذاشته و فونت `storage/fonts/Vazirmatn-Regular.ttf` هم در آرشیو هست، اما:
  - `BillingController.php:158` با `'defaultFont' => 'DejaVu Sans'` آن را override می‌کند،
  - `resources/views/billing/print.blade.php:13` هم `font-family: 'DejaVu Sans'` دارد،
  - هیچ `@font-face` یا ثبت فونت در Dompdf وجود ندارد.
  نتیجه: حروف فارسی در PDF به‌صورت مربع/ناموجود درمی‌آیند (DejaVu Sans گلیف فارسی ندارد).
- `print.blade.php:2` تگ `<html>` بدون `dir="rtl"` است؛ چیدمان متن ترکیبی فارسی/عدد اشتباه می‌شود.
- `BillingController.php:160` مقدار `isPhpEnabled => true` را روی config (`enable_php => false`) می‌گذارد. برای یک فاکتور نیازی نیست و سطح حمله PDF را بالا می‌برد؛ باید `false` باشد.

### ۲.۶ باگ چک‌باکس فرم صدور فاکتور
در `resources/views/billing/create.blade.php`، چک‌باکس هم `data-index` دارد (خط ۵۸) و `toggleRowInputs` (خط ۱۱۱-۱۱۶) همه عناصر دارای `data-index` را disable/enable می‌کند. وقتی کاربر یک واگن را **از حالت انتخاب خارج** کند، خودِ چک‌باکس هم `disabled` می‌شود و دیگر قابل انتخاب مجدد نیست.

**اصلاح:** چک‌باکس را از این حلقه مستثنی کنید:
```js
if (el.classList.contains('cost-preview') || el.classList.contains('wagon-check')) return;
```

### ۲.۷ حذف سخت کاربرِ ارجاع‌شده
`UserController::destroy` (`UserController.php:81-86`) کاربر را hard-delete می‌کند، در حالی که `billings.operator_id` و `cargo_requests.operator_id` به آن ارجاع دارند. نتیجه: خطای FK (اگر migration درست باشد) یا NULL شدن operator و سپس ۵۰۰ در `billing/show.blade.php:15` و `cargo/show.blade.php:12` که `->operator->name` را بدون guard می‌خوانند.
**اصلاح:** soft delete برای `users` + غیرفعال‌سازی به‌جای حذف + `?->name` در نماها.

---

## ۳) فیلترها، UI و وابستگی‌ها (P2)

### ۳.۱ فیلتر جستجو + فیلتر دیگر، ترکیب اشتباه SQL
- `TariffController.php:14-16`:
```php
->when($search, fn ($q) => $q->where('title','like',...)->orWhere('code','like',...))
->when($equipment_type, fn ($q) => $q->where('equipment_type', ...))
```
بدون گروه‌بندی، SQL می‌شود `title LIKE ? OR (code LIKE ? AND equipment_type = ?)`؛ بنابراین نتایج جستجو فیلتر نوع دستگاه را دور می‌زنند.
- `UserController.php:15-17` دقیقاً همین مشکل را با `role` دارد.
**اصلاح:** `fn ($q) => $q->where(fn ($q) => $q->where(...)->orWhere(...))`.

### ۳.۲ صفحه‌بندی Tailwind در قالب Bootstrap
هیچ‌جا `Paginator::useBootstrapFive()` صدا زده نشده (`AppServiceProvider::boot` خالی است) و `resources/css/app.css` هم Tailwind است که در layout بارگذاری نمی‌شود. ۱۳ ویو `->links()` دارند؛ خروجی پیش‌فرض لاراول Tailwind است و در قالب Bootstrap 5 RTL بدون استایل/شکسته دیده می‌شود. یک خط در `AppServiceProvider` کافی است.

### ۳.۳ وابستگی‌های فرانت ناسازگار
`package.json`:
- `"bootstrap-rtl": "^3.3.4"` → این پکیج RTL مخصوص **Bootstrap 3** است، اما کل نماها مارک‌آپ Bootstrap 5 دارند (`form-select`, `data-bs-toggle`, `offset-md-8`, ...). بررسی tarball نشان می‌دهد این پکیج فقط `dist/css/bootstrap-flipped.css` و JS نسخه Bootstrap 3 را دارد؛ فایل‌هایی که layout می‌خواهد (`bootstrap.rtl.min.css` و `bootstrap.bundle.min.js`) اصلاً در آن نیستند. باید از build رسمی Bootstrap 5 (`bootstrap/dist/css/bootstrap.rtl.min.css` و `bootstrap/dist/js/bootstrap.bundle.min.js`) استفاده شود.
- `"jquery": "^4.0.0"` در حالی که `persian-datepicker@1.2.0` خودش به `jquery: ^3.3.1` وابسته است. jQuery 4 چند API قدیمی را حذف کرده؛ احتمال شکستن دیت‌پیکر بالاست. نسخه ۳.۷.۱ را pin کنید.
- `persian-datepicker` به `persian-date: next` وابسته است (نسخه pre-release)؛ باید با `overrides` نسخه پایدار pin شود.
- `resources/css/app.css` (Tailwind 4) و Vite استفاده نمی‌شوند؛ در عوض layout به `public/css/app.css` اشاره می‌کند که وجود ندارد.

### ۳.۴ پیام warning هیچ‌جا نمایش داده نمی‌شود
`layouts/app.blade.php` فقط `session('success')` و `$errors` را نشان می‌دهد. هر `session()->flash('warning', ...)` در کنترلرها بی‌اثر است (بند ۲.۴).

### ۳.۵ وضعیت‌ها نیمه‌کاره‌اند
- `cargo_requests.status` فقط `pending` و `billed` می‌شود (`CargoController.php:75`, `BillingController.php:136`)؛ وضعیت `loaded` هرگز ست نمی‌شود، ولی در فیلتر `cargo/index.blade.php:15` و `cargo/index.blade.php:38` به کاربر نشان داده می‌شود.
- `billings.status` همیشه `issued` است؛ لغو/پرداخت‌شده/تسویه‌شده وجود ندارد. `SoftDeletes` روی `Billing` بدون مسیر حذف/بازیابی است.
- بنابراین «چرخه بارگیری» ناقص است: درخواست → (بدون ثبت بارگیری) → فاکتور. وزن و تعرفه فقط لحظه صدور فاکتور ثبت می‌شوند. برای سامانه بارگیری، یک مرحله ثبت عملیات بارگیری (تاریخ، وزن، دستگاه) لازم است.

### ۳.۶ گزارش‌های نادرست/گمراه‌کننده
- `reports/wagon-detail.blade.php` در هر ردیف واگن، **کل مالیات فاکتور** را تکرار می‌کند؛ جمع ستون با واقعیت نمی‌خواند چون مالیات per-item ذخیره نشده است.
- `reports/financial.blade.php` تاریخ میلادی `billing_date` را نشان می‌دهد، در حالی که بقیه سیستم شمسی است.
- `tax/edit.blade.php` تاریخ ثبت را میلادی نشان می‌دهد.
- «گزارش روزانه بارگیری» در واقع درخواست‌ها را می‌شمارد، نه بارگیری‌ها؛ نمودار «بارگیری» داشبورد هم از `billing_items.loaded_weight` می‌آید، یعنی فقط بعد از فاکتور داده دارد.
- گزارش عملکرد بر اساس `users.name` / `owners.name` گروه می‌شود؛ دو نفر هم‌نام با هم ادغام می‌شوند. باید بر اساس `id` گروه شود.
- نمودارهای داشبورد همیشه ۷ روز اخیرند و فیلتر بازه تاریخ روی آن‌ها اثر ندارد (`DashboardController::dailyLoadingTons` / `dailyRevenue`).

### ۳.۷ فیلترهای مرده
`BillingController::index` پارامترهای `owner_id` و `status` را می‌خواند (`BillingController.php:20-23`) اما فرم فیلتری در `billing/index.blade.php` وجود ندارد. `CargoController::index` هم `owner_id` را پشتیبانی می‌کند ولی UI ندارد.

### ۳.۸ فرم‌ها بعد از خطای اعتبارسنجی خالی می‌شوند
- `billing/create.blade.php` هیچ `old()` برای واگن/تعرفه/وزن/تاریخ ندارد؛ با یک خطای اعتبارسنجی، کل انتخاب‌ها از دست می‌رود.
- `cargo/create.blade.php` شماره واگن‌ها را با `old()` برنمی‌گرداند و کاربر باید همه را دوباره وارد کند.

### ۳.۹ عدم نمایش پیام‌های فارسی اعتبارسنجی
پوشه `lang/` وجود ندارد و `APP_LOCALE=en` است؛ پیام‌های پیش‌فرض لاراول انگلیسی‌اند و فقط نام فیلدها فارسی می‌شود (مثلاً «The صاحب کالا field is required»). نیاز به `lang/fa/validation.php` یا پکیج ترجمه فارسی.

### ۳.۱۰ نمایش وضعیت‌ها با متن انگلیسی
`billing/index.blade.php:22`، `dashboard/index.blade.php:62`، `reports/*` مقدار `issued` را خام نشان می‌دهند؛ فقط `cargo/index` نگاشت فارسی دارد. بهتر است labelها در Model به‌صورت constant/accessor تعریف شوند.

---

## ۴) نکات کوچک‌تر و کیفیت کد (P3)

1. **`TaxController::update` بدون تراکنش** همه رکوردهای current را false می‌کند و بعد رکورد جدید می‌سازد؛ با خطا یا همزمانی ممکن است هیچ رکورد current نماند / دو رکورد current بسازد.
2. **`Billing::generateBillingNumber` از `count` و SoftDeletes** (بند ۲.۲)؛ `CargoRequest` هم مشابه.
3. **`billing_items` snapshot ناقص:** عنوان/نرخ/ظرفیت تعرفه کپی می‌شود، اما نام/کد ملی صاحب کالا و نماینده نه؛ با تغییر نام، فاکتورهای قدیمی هم عوض می‌شوند. برای حسابداری، snapshot هویتی هم توصیه می‌شود.
4. **وزن بیشتر از ظرفیت:** `BillingItem::calculateBaseCost` هیچ سقفی ندارد؛ اگر وزن > ظرفیت باشد هزینه از نرخ کامل بیشتر می‌شود. اگر قاعده کسب‌وکار «حداکثر ۱۰۰٪» است، باید cap شود یا اعتبارسنجی وزن <= ظرفیت اضافه شود.
5. **شماره واگن تکراری:** نه در فرم درخواست و نه در DB جلوگیری نمی‌شود. ایندکس `unique(cargo_request_id, wagon_number)` و validation `distinct` لازم است.
6. **`users.store` مقدار `is_active` را ست نمی‌کند** (`UserController.php:28-42`)؛ به default دیتابیس وابسته است که migration غایب است.
7. **`RepresentativeController::index`** فیلتر search دارد و کار می‌کند (بررسی شد)؛ فقط فیلتر وضعیت ندارد.
8. **`Tariff.category`** فقط ذخیره/اعتبارسنجی می‌شود و هیچ‌جا استفاده نمی‌شود؛ فیلد مرده.
9. **`Owner`/`Representative` soft delete** دارند اما مسیر حذف ندارند؛ اگر در آینده اضافه شود، `billing/index.blade.php:17` و `billing/show.blade.php:12` با `$b->owner->name` بدون guard می‌شکنند.
10. **`welcome.blade.php`** فایل پیش‌فرض لاراول با Tailwind و متن انگلیسی است و در UI این پروژه استفاده نمی‌شود؛ حذف یا جایگزینی با redirect به داشبورد.
11. **`number_format` بدون واحد/جداکننده فارسی** و استفاده از ارقام لاتین در جدول‌ها؛ برای فاکتور فارسی بهتر است ارقام فارسی یا فرمت یکدست استفاده شود.
12. **`APP_NAME=Laravel`** در `.env` است؛ نام سامانه در عنوان‌ها ثابت است ولی config/env ناهماهنگ است.
13. **`.env`, `storage/logs/laravel.log` (۹۳۳ خط با مسیرهای ویندوز و شناسه کاربر), `storage/framework/views/*.php` و `bootstrap/cache/*.php`** نباید در خروجی پروژه باشند. این فایل‌ها با وجود ignore داخلی پوشه‌ها (`.gitignore`های `storage/logs` و `bootstrap/cache` با `*`) در آرشیو آمده‌اند؛ یعنی آرشیو بدون احترام به git بسته‌بندی شده است.
14. **`composer.lock` در آرشیو هست** ولی README می‌گوید بدون lock تحویل داده شده؛ ضمناً content-hash آن با composer.json دست‌کاری‌شده (افزودن autoload Morilog) ممکن است هم‌خوان نباشد و `composer install` هشدار بدهد.
15. **`users.destroy` و `abort_if(auth()->id())`** منطق خوبی دارد ولی حذف کاربر باید soft باشد (بند ۲.۷).
16. **بدون throttle روی ورود**؛ چون `routes/` نیست قابل بررسی نیست، اما در بازسازی حتماً `->middleware('throttle:5,1')` روی `login.submit` بگذارید.
17. **رمزهای پیش‌فرض در README عمومی** (`admin@wagon.local / Admin@123`)؛ اگر همین repo عمومی بماند، هر کسی می‌تواند بعد از استقرار وارد شود. باید در اولین اجرا اجباری به تغییر رمز شود یا seeder رمز تصادفی بسازد.
18. **بدون Audit Log** برای تغییر تعرفه/مالیات/فاکتور؛ برای سیستم مالی توصیه می‌شود.
19. **پول با float** محاسبه می‌شود (`(weight/capacity)*rate` و `round`). برای مبالغ ریالی بزرگ بهتر است از integer (یا محاسبه در «ریال × ۱۰۰») استفاده شود تا اختلاف PHP/JS در مرز ۰.۵ رخ ندهد.
20. **`resources/views/layouts/app.blade.php` همه اسکریپت‌ها (Chart.js، datepicker) را در همه صفحات لود می‌کند**؛ در صورت ۴۰۴ بودن فایل‌ها، همه صفحات خطای JS می‌دهند. بارگذاری شرطی بهتر است.

---

## ۵) تناقض‌های README با واقعیت آرشیو

| ادعای README | واقعیت |
|---|---|
| «تمام migrationها و seeders» | پوشه `database/` وجود ندارد |
| «routes/web.php شامل تمام روت‌ها» | فایل نیست |
| «پروژه بدون قفل composer» | `composer.lock` هست |
| «فونت PDF = Vazirmatn» | کنترلر و ویو `DejaVu Sans` را override می‌کنند |
| «`composer install` کافی است» | بدون routes/database/public برنامه بالا نمی‌آید |
| «`cp .env.example .env`» | `.env` تحویلی توسعه‌ای و کاملاً متفاوت است، با `APP_KEY` واقعی |

---

## ۶) نقاط قوت (که باید حفظ شوند)

- ساختار ماژولار و نام‌گذاری تمیز؛ هر حوزه کنترلر/مدل/ویو مستقل دارد.
- صدور فاکتور داخل `DB::transaction` و به‌روزرسانی وضعیت واگن‌ها در همان تراکنش.
- Snapshot تعرفه و درصد مالیات در فاکتور → تغییرات آینده، فاکتورهای قبلی را خراب نمی‌کند (اصل درست حسابداری).
- ذخیره موازی تاریخ میلادی (برای بازه/مرتب‌سازی) و شمسی (برای نمایش) — تصمیم درستی است.
- میدل‌ور `CheckRole` + بررسی `is_active` در ورود + منع غیرفعال‌سازی خود.
- SoftDeletes روی موجودیت‌های اصلی و pagination با `withQueryString`.
- برچسب‌ها و پیام‌های فارسی، RTL، و README مفصل و آگاه به محدودیت آفلاین.

---

## ۷) پیشنهاد ترتیب اصلاح

**فاز ۱ — اجرا (نیم‌روز):**
1. ساخت `routes/web.php` و `routes/console.php` طبق پیوست ب.
2. ساخت ۱۰ migration + migrationهای پیش‌فرض لاراول (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`) + seeder کاربر/تعرفه/مالیات.
3. ساخت `public/index.php` + دارایی‌های آفلاین (Bootstrap 5 RTL رسمی، jQuery 3.7.1، datepicker، Chart.js، فونت).
4. حذف `.env` از خروجی، ساخت از `.env.example`، `key:generate`، `timezone => env('APP_TIMEZONE','Asia/Tehran')`.
5. حذف هک‌های require دستی و نگاشت autoload Morilog.

**فاز ۲ — صحت مالی (۱ تا ۲ روز):**
6. اعتبارسنجی سخت‌گیرانه سبد فاکتور (بند ۲.۱) + unique index روی `billing_items.wagon_id`.
7. شماره‌گذاری اتمی فاکتور/درخواست (بند ۲.۲).
8. اصلاح جمع گزارش‌ها (بند ۲.۳).
9. helper تاریخ شمسی + حذف fallback خاموش + نمایش warning.
10. اصلاح PDF (فونت Vazirmatn، `dir="rtl"`, `isPhpEnabled=false`).

**فاز ۳ — UX/گزارش (۱ روز):**
11. رفع باگ چک‌باکس و حفظ `old()` در فرم‌ها.
12. `Paginator::useBootstrapFive()`، رفع OR بدون گروه، اصلاح نسخه‌های npm، پیام‌های اعتبارسنجی فارسی.
13. وضعیت‌های فارسی و چرخه کامل بارگیری (ثبت وزن/دستگاه قبل از فاکتور).
14. گزارش‌های per-item (مالیات سهم هر واگن) و تاریخ شمسی در همه گزارش‌ها.

**فاز ۴ — امنیت/نگهداشت:**
15. soft delete کاربر، throttle ورود، حذف فایل‌های تولیدی از خروجی، Audit log، بک‌آپ MySQL.

---

## پیوست الف — جدول‌های لازم (استنباط‌شده از مدل‌ها)

```
users(id, name, email, password, role, is_active, phone, timestamps, remember_token)
owners(id, name, national_id unique nullable, economic_code, phone, address,
       description, is_active default 1, timestamps, deleted_at)
representatives(id, name, national_id unique nullable, phone, is_active, timestamps, deleted_at)
owner_representative(owner_id, representative_id, unique pair)
tariffs(id, code unique, rate, unit, title, category, capacity, equipment_type, is_active, timestamps)
tax_settings(id, percentage, is_current, updated_by, timestamps)
cargo_requests(id, request_number unique, owner_id, representative_id, operator_id,
               request_date, request_date_shamsi, status, notes, timestamps)
wagons(id, cargo_request_id, wagon_number, tariff_id nullable, loaded_weight nullable,
       is_settled default 0, timestamps, unique(cargo_request_id, wagon_number))
billings(id, billing_number unique, owner_id, representative_id nullable, operator_id,
         billing_date, billing_date_shamsi, base_amount, tax_percentage, tax_amount,
         total_amount, status, notes, timestamps, deleted_at)
billing_items(id, billing_id, wagon_id unique, tariff_id, tariff_title, tariff_rate,
              tariff_capacity, loaded_weight, base_cost, timestamps)
```
به‌علاوه جدول‌های پیش‌فرض لاراول برای session/cache/queue (چون `.env` فعلی database driver است) یا تغییر driver به `file`/`sync` طبق `.env.example`.

## پیوست ب — روت‌های لازم (از فراخوانی‌های `route()` و متدهای کنترلر)

```php
// عمومی
GET  /login                      → login            (AuthController@showLogin)
POST /login                      → login.submit     (AuthController@login)

Route::middleware('auth')->group(function () {
    POST /logout                 → logout

    GET  /                       → dashboard        (DashboardController@index)

    // بارگیری
    GET  /cargo                  → cargo.index
    GET  /cargo/create           → cargo.create
    POST /cargo                  → cargo.store
    GET  /cargo/{cargo}          → cargo.show       // نام پارامتر باید cargo باشد

    // صورتحساب
    GET  /billing                → billing.index
    GET  /billing/create         → billing.create
    POST /billing                → billing.store
    GET  /billing/{billing}      → billing.show
    GET  /billing/{billing}/print→ billing.print

    // گزارش‌ها
    GET /reports/daily-loading   → reports.daily-loading
    GET /reports/billings        → reports.billings
    GET /reports/operators       → reports.operators
    GET /reports/financial       → reports.financial
    GET /reports/wagon-detail    → reports.wagon-detail
    GET /reports/owners          → reports.owners
    GET /reports/representatives → reports.representatives

    // مدیریت — role:admin
    Route::middleware('role:admin')->group(function () {
        GET    /owners                     → owners.index
        GET    /owners/create              → owners.create
        POST   /owners                     → owners.store
        GET    /owners/{owner}/edit        → owners.edit
        PUT    /owners/{owner}             → owners.update
        PATCH  /owners/{owner}/toggle      → owners.toggle
        GET    /owners/{owner}/history     → owners.history

        GET    /representatives                    → representatives.index
        GET    /representatives/create             → representatives.create
        POST   /representatives                    → representatives.store
        GET    /representatives/{representative}/edit    → representatives.edit
        PUT    /representatives/{representative}         → representatives.update
        PATCH  /representatives/{representative}/toggle  → representatives.toggle
        GET    /representatives/{representative}/history → representatives.history

        GET    /tariffs                    → tariffs.index
        GET    /tariffs/create             → tariffs.create
        POST   /tariffs                    → tariffs.store
        GET    /tariffs/{tariff}/edit      → tariffs.edit
        PUT    /tariffs/{tariff}           → tariffs.update
        PATCH  /tariffs/{tariff}/toggle    → tariffs.toggle

        GET    /tax                        → tax.edit
        POST   /tax                        → tax.update

        GET    /users                      → users.index
        GET    /users/create               → users.create
        POST   /users                      → users.store
        GET    /users/{user}/edit          → users.edit
        PUT    /users/{user}               → users.update
        PATCH  /users/{user}/toggle        → users.toggle
        DELETE /users/{user}               → users.destroy
    });
});
```

**نکته:** `report`ها در layout برای همه نقش‌ها نمایش داده می‌شوند، پس خارج از گروه admin تعریف شوند (یا اگر باید محدود شوند، منو هم همان‌قدر محدود شود).
