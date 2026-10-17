# سامانه مدیریت بارگیری واگن

سامانه‌ای فارسی و راست‌به‌چپ (RTL) برای ثبت درخواست‌های بارگیری واگن، ثبت وزن/تعرفه بارگیری، صدور صورتحساب با مالیات، لغو صورتحساب و گزارش‌های مالی و عملیاتی. رابط کاربری کاملاً آفلاین است و هیچ CDN یا سرویس خارجی لازم ندارد.

- **نسخه Laravel:** ۱۲
- **دیتابیس پیش‌فرض:** SQLite (تک‌فایلی و بدون نصب سرور دیتابیس)
- **تاریخ:** ذخیره‌سازی هم‌زمان میلادی (برای محاسبات) و شمسی (برای نمایش)
- **زبان:** فارسی، منطقه زمانی `Asia/Tehran`

---

## امکانات

| بخش | توضیح |
|---|---|
| داشبورد | شاخص‌های کلیدی، نمودار بارگیری/درآمد/سهم صاحبان کالا و فیلتر بازه شمسی |
| درخواست بارگیری | ثبت درخواست با چند واگن، شماره‌گذاری خودکار، جلوگیری از شماره واگن تکراری |
| ثبت بارگیری | تعیین تعرفه و وزن هر واگن، کنترل ظرفیت، تاریخ شمسی، وضعیت‌های «در انتظار / بارگیری‌شده / صورتحساب‌شده» |
| صورتحساب | صدور فاکتور از واگن‌های بارگیری‌شده، محاسبه هزینه پایه و مالیات، snapshot تعرفه/مالیات/نام‌ها، لغو و آزادسازی واگن‌ها |
| گزارش‌ها | گزارش روزانه، صورتحساب‌ها (جمع کل مستقل از صفحه‌بندی)، عملکرد اپراتور/صاحب کالا/نماینده، گزارش مالی و گزارش تفصیلی واگن |
| مدیریت | صاحبان کالا، نمایندگان (چند صاحب کالا برای هر نماینده)، تعرفه‌ها، درصد مالیات با تاریخچه، کاربران |
| امنیت | ورود با throttle، نقش `admin`/`operator`، غیرفعال‌سازی فوری کاربر، soft delete کاربران، CSRF، اعتبارسنجی فارسی |

---

## پیش‌نیازها

- **PHP 8.2 یا بالاتر** با افزونه‌های:
  `mbstring`، `openssl`، `pdo_sqlite`، `tokenizer`، `ctype`، `filter`، `session`، و **`dom`** (برای PDF؛ استاندارد در اکثر نصب‌های PHP).
- **Composer 2**
- Node.js فقط در صورت تمایل به بازتولید فایل‌های آفلاین؛ برای اجرا **لازم نیست** (دارایی‌ها از قبل در `public/vendor/` قرار دارند).

> ویندوز: `extension=dom` و `extension=mbstring` را در `php.ini` فعال کنید. در XAMPP معمولاً هر دو فعال‌اند.

---

## نصب سریع

```bash
composer install
composer run setup
php artisan serve
```

سپس در مرورگر: <http://127.0.0.1:8000>

`composer run setup` این کارها را انجام می‌دهد:

1. ساخت `.env` از `.env.example` (اگر وجود نداشته باشد)
2. تولید `APP_KEY`
3. ساخت فایل `database/database.sqlite`
4. اجرای migrationها
5. اجرای seederها

### رمز کاربر ادمین

رمز پیش‌فرض ثابتی وجود ندارد. دو راه دارید:

**راه ۱ (پیشنهادی):** قبل از seed، مقدار رمز را در `.env` بگذارید:

```dotenv
SEED_ADMIN_EMAIL=admin@wagon.local
SEED_ADMIN_PASSWORD=یک-رمز-قوی-حداقل-۸-کاراکتر
```

**راه ۲:** بگذارید رمز تصادفی ساخته شود؛ seeder آن را یک بار در خروجی ترمینال نمایش می‌دهد. اگر خروجی را ندیدید (مثلاً هنگام `migrate --seed` که خروجی seeder بی‌صدا است):

```bash
php artisan admin:reset-password admin@wagon.local
```

این دستور یک رمز تصادفی می‌سازد و نمایش می‌دهد. می‌توانید رمز دلخواه هم بدهید:

```bash
php artisan admin:reset-password admin@wagon.local --password="MyStrongPass-1404"
```

> کاربر اپراتور نمونه (`operator@wagon.local`) هم با رمز تصادفی ساخته می‌شود. پس از ورود با ادمین، از بخش «کاربران → ویرایش» می‌توانید رمز او را تعیین کنید.

---

## رفع خطای 500 (Server Error)

اگر بعد از آپلود پروژه صفحه «500 Server Error» می‌بینید، در ۹۹٪ موارد یکی از این دلایل است. فایل `diagnose.php` را در مرورگر باز کنید:

```
https://دامنه-شما/diagnose.php
```

این صفحه همه پیش‌نیازها را چک می‌کند و دقیقاً می‌گوید کدام مورد مشکل دارد (بعد از رفع خطا، فایل را حذف کنید).

### ۱) پوشه `vendor` وجود ندارد (شایع‌ترین دلیل)
فایل zip فقط شامل کد پروژه است و پوشه `vendor` داخل آن نیست. روی سرور (یا سیستم خودتان) اجرا کنید:

```bash
composer install
```

اگر Composer روی هاست نیست، از پشتیبانی بخواهید نصب/فعال کند. بدون `vendor`، Laravel حتی یک خطا هم در لاگ ثبت نمی‌کند و فقط صفحه ۵۰۰ می‌دهد.

> از نسخه فعلی، `public/index.php` اگر `vendor` را نبیند پیام فارسی با راه‌حل نشان می‌دهد (نه صفحه ۵۰۰ خالی).

### ۲) `APP_KEY` خالی است
```bash
php artisan key:generate
```
اگر فایل `.env` وجود ندارد، ابتدا از `.env.example` کپی بگیرید.

### ۳) دسترسی نوشتن به `storage` و `bootstrap/cache`
```bash
chmod -R 775 storage bootstrap/cache
```
و مالکیت را به کاربر وب‌سرور بدهید (مثلاً `www-data` یا در cPanel کاربر هاست). روی ویندوز پوشه پروژه نباید Read-only باشد.

### ۴) جدول‌های دیتابیس ساخته نشده‌اند
```bash
touch database/database.sqlite
php artisan migrate --seed
```

### ۵) نسخه PHP کمتر از 8.2 است
در cPanel → «Select PHP Version» نسخه 8.2 یا 8.3 را فعال کنید. افزونه‌های `mbstring`، `openssl`، `pdo_sqlite` و برای PDF `dom` هم باید فعال باشند.

### ۶) ریشه سایت روی `public` نیست
بهترین حالت: Document Root را روی پوشه `public` بگذارید.
اگر امکان تغییر نیست، فایل `.htaccess` ریشه (که همراه پروژه هست) درخواست‌ها را به `public` هدایت می‌کند؛ فقط باید `mod_rewrite` و `AllowOverride` فعال باشد.

### ۷) دیدن خطای واقعی
در فایل `.env` موقتاً بگذارید:

```dotenv
APP_DEBUG=true
```

بعد صفحه را رفرش کنید یا فایل `storage/logs/laravel.log` را ببینید. بعد از پیدا کردن مشکل حتماً `APP_DEBUG=false` کنید.

### ۸) تست مسیر بدون مرورگر
```bash
php -v
php artisan --version
php artisan migrate --seed
```
اگر `php artisan` اجرا نشد، مشکل در PHP/Composer است، نه در کد برنامه.

---

## نصب دستی (بدون اسکریپت)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

در ویندوز می‌توانید فایل `database/database.sqlite` را دستی بسازید.

برای اجرا روی Apache/Nginx، **document root** را روی پوشه `public` تنظیم کنید؛ فایل `public/.htaccess` برای Apache آماده است.

---

## تنظیمات مهم (`.env`)

| کلید | پیش‌فرض | توضیح |
|---|---|---|
| `APP_TIMEZONE` | `Asia/Tehran` | مبنای «امروز»، نمودارها و شماره‌گذاری اسناد |
| `APP_LOCALE` | `fa` | پیام‌های اعتبارسنجی و خطاها فارسی |
| `DB_CONNECTION` | `sqlite` | برای SQLite نیازی به `DB_DATABASE` نیست؛ مسیر پیش‌فرض `database/database.sqlite` است |
| `SESSION_DRIVER` | `file` | بدون نیاز به جدول session |
| `CACHE_STORE` | `file` | ذخیره throttle ورود |
| `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` | — | فقط هنگام seed خوانده می‌شوند |
| `DB_FOREIGN_KEYS` | `true` | فعال بودن کلیدهای خارجی SQLite |

پس از تغییر `.env` در محیط عملیاتی:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> در حالت توسعه این دستورات را اجرا نکنید یا با `php artisan optimize:clear` پاک کنید.

---

## ساختار پروژه

```
app/
  Console/Commands/ResetAdminPassword.php
  Http/Controllers/         Auth, Dashboard, Cargo, Billing, Owner,
                            Representative, Tariff, Tax, User, Report
  Http/Middleware/          CheckRole, EnsureUserIsActive
  Models/                   User, Owner, Representative, Tariff, TaxSetting,
                            CargoRequest, Wagon, Billing, BillingItem
  Providers/AppServiceProvider.php
  Support/                  JalaliDate, DocumentNumber
config/                     app, auth, cache, database, dompdf, jalali, ...
database/
  migrations/               جداول سیستم + users/sessions/cache/jobs
  seeders/                  کاربر ادمین/اپراتور، تعرفه‌های پایه، درصد مالیات
lang/fa/                    validation, auth, passwords, pagination
public/
  index.php (با بررسی پیش‌نیازها و پیام فارسی)، .htaccess
  css/app.css
  vendor/                   Bootstrap 5 RTL، jQuery، datepicker، Chart.js، فونت وزیرمتن
resources/views/            ۳۳ نمای Blade (layouts، auth، dashboard، cargo، billing،
                            owners، representatives، tariffs، tax، users، reports)
routes/web.php              تمام مسیرهای وب
.htaccess                   هدایت به public برای هاست‌هایی که document root را
                            نمی‌شود تغییر داد
diagnose.php                ابزار عیب‌یابی پیش‌نیازها (بعد از رفع خطا حذف شود)
scripts/                    کپی و بررسی دارایی‌های آفلاین (اختیاری)
storage/fonts/              فونت‌های TTF برای PDF (Vazirmatn Regular/Bold)
tests/                      تست‌های واحد و Feature
```

---

## دارایی‌های آفلاین

همه فایل‌های CSS/JS/فونت در `public/vendor/` هستند و در `public/` سرو می‌شوند؛ هیچ درخواستی به اینترنت زده نمی‌شود.

اگر خواستید نسخه‌ها را از `node_modules` بازتولید کنید:

```bash
npm install
npm run assets
npm run assets:check
```

نسخه‌های استفاده‌شده: Bootstrap 5.3.8 (RTL رسمی)، jQuery 3.7.1، persian-date 1.1.0، persian-datepicker 1.2.0، Chart.js 4.5.1، فونت Vazirmatn 33.0.3.

> `package.json` فقط برای همین کار است؛ برنامه به Vite یا مرحله build نیاز ندارد.

---

## تست‌ها

```bash
php artisan test
```

تست‌ها از SQLite در حافظه استفاده می‌کنند (`phpunit.xml`) و دیتابیس اصلی را تغییر نمی‌دهند. پوشش فعلی:

- `tests/Unit/JalaliDateTest.php` — نرمال‌سازی ارقام فارسی/عربی، اعتبار تاریخ شمسی، کبیسه
- `tests/Feature/BillingFlowTest.php` — چرخه کامل درخواست → بارگیری → صورتحساب → لغو و آزادسازی
- `tests/Feature/ValidationTest.php` — تاریخ نامعتبر، ارقام فارسی، ظرفیت، واگن تکراری، نماینده نامرتبط
- `tests/Feature/AccessControlTest.php` — مهمان/اپراتور/ادمین، کاربر غیرفعال، محافظت از حساب خود
- `tests/Feature/ReportsTest.php` — جمع کل گزارش مستقل از صفحه‌بندی و تاریخ شمسی

---

## PDF صورتحساب

- فونت `Vazirmatn` از `storage/fonts/*.ttf` با `@font-face` و `file://` بارگذاری می‌شود؛ نیازی به دسترسی اینترنت نیست.
- چیدمان کاملاً RTL است و `isPhpEnabled=false` قرار گرفته است.
- پوشه `storage/fonts` باید قابل نوشتن باشد (Dompdf کش فونت می‌سازد).
- برای تولید PDF، افزونه `dom` الزامی است. اگر این افزونه روی سرور فعال نباشد، به‌جای خطای ۵۰۰ همان فاکتور به‌صورت **HTML قابل چاپ** نمایش داده می‌شود و با Ctrl+P می‌توانید آن را به PDF ذخیره کنید.

---

## پشتیبان‌گیری و نگهداری

- کل داده‌ها در `database/database.sqlite` است. برای بک‌آپ، هنگام توقف برنامه این فایل (و در صورت وجود `-wal`/`-shm`) را کپی کنید.
- فایل `.env` و `storage/logs/` را در مخزن عمومی قرار ندهید (در `.gitignore` هستند).
- رمزها با bcrypt هش می‌شوند؛ رمز اولیه را بعد از اولین ورود تغییر دهید.
- برای محیط عملیاتی `APP_DEBUG=false` و HTTPS (به همراه `SESSION_SECURE_COOKIE=true`) توصیه می‌شود.

---

## محدودیت‌های شناخته‌شده

- **Audit Log کامل** (ثبت تاریخچه تغییرات تعرفه/مالیات/فاکتور) پیاده‌سازی نشده است؛ فقط تاریخچه درصد مالیات و snapshotهای فاکتور وجود دارد.
- چرخه پرداخت (پرداخت‌شده/تسویه‌شده) و گزارش‌های پرداخت وجود ندارد؛ لغو فاکتور، واگن‌ها را آزاد می‌کند.
- محاسبات مالی به ریال و با `round` انجام می‌شود؛ برای واحدهای پولی با اعشار، بهتر است محاسبات در «ریال × ۱۰۰» انجام شود.
- SQLite برای حجم‌های بالا و نوشتن هم‌زمان زیاد مناسب نیست؛ در آن صورت استفاده از MySQL/MariaDB توصیه می‌شود.
- تمایل به ارقام فارسی در همه جدول‌ها وجود دارد اما خروجی‌ها با ارقام لاتین و جداکننده هزارگان نمایش داده می‌شوند.

---

## مستند بررسی و اصلاحات

فایل [`REVIEW.md`](REVIEW.md) گزارش کامل بررسی نسخه اولیه (باگ‌ها، امنیت، UI، تناقض‌های README) و **وضعیت اصلاح هر مورد** را در ابتدای فایل دارد. خلاصه: تمام موارد مسدودکننده (P0) و باگ‌های مالی/داده‌ای (P1) برطرف شده‌اند؛ موارد کم‌اهمیت‌تر یا خارج از دامنه (مانند Audit Log) در همان فایل با دلیل مشخص شده‌اند.
