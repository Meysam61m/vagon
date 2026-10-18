# سامانه مدیریت بارگیری واگن

سامانه‌ای فارسی و راست‌به‌چپ (RTL) برای ثبت درخواست‌های بارگیری واگن، ثبت وزن/تعرفه بارگیری، صدور صورتحساب با مالیات، لغو صورتحساب و گزارش‌های مالی و عملیاتی. رابط کاربری کاملاً آفلاین است و هیچ CDN یا سرویس خارجی لازم ندارد.

- **نسخه Laravel:** ۱۲
- **دیتابیس:** **MySQL 8 / MariaDB 10.6+** (زبان و collation: `utf8mb4_unicode_ci`)
- **تاریخ:** ذخیره‌سازی هم‌زمان میلادی (برای محاسبات) و شمسی (برای نمایش)
- **زبان:** فارسی، منطقه زمانی `Asia/Tehran`

> نسخه قبلی روی SQLite بود. اکنون دیتابیس اصلی **MySQL** است. برای سازگاری، پشتیبانی از SQLite هم باقی مانده ولی فقط برای تست‌های خودکار و اجرای سبک محلی استفاده می‌شود.

---

## امکانات

| بخش | توضیح |
|---|---|
| داشبورد | شاخص‌های کلیدی، نمودار بارگیری/درآمد/سهم صاحبان کالا، فیلتر بازه شمسی، و **آمار و فهرست واگن‌های ویرایش‌شده (اصلاح شماره واگن / تغییر صاحب کالا پس از صورتحساب همراه با ذکر علت)** |
| درخواست بارگیری | ثبت درخواست با چند واگن، **شرح کالای اجباری برای هر واگن** (پیشنهاد خودکار از واگن قبل)، شماره‌گذاری خودکار، جلوگیری از شماره واگن تکراری |
| ثبت بارگیری | تعیین تعرفه و وزن هر واگن (شرح کالا برای واگن تازه‌بارگیری‌شده اجباری است)، کنترل ظرفیت، تاریخ شمسی، وضعیت‌های «در انتظار / بارگیری‌شده / صورتحساب‌شده» |
| صورتحساب | صدور فاکتور از واگن‌های بارگیری‌شده، نمایش شرح کالای هر واگن در فاکتور، **امکان اصلاح شماره واگن اشتباه و ویرایش صاحب کالای واگن پس از صدور صورتحساب با الزام به ذکر علت و ثبت تاریخچه در داشبورد مدیر**، محاسبه هزینه پایه و مالیات، snapshot تعرفه/مالیات/نام‌ها، لغو و آزادسازی واگن‌ها |
| گزارش‌ها | گزارش روزانه، صورتحساب‌ها (جمع کل مستقل از صفحه‌بندی)، عملکرد اپراتور/صاحب کالا/نماینده، گزارش مالی و گزارش تفصیلی واگن — **شرح کالا در گزارش روزانه، گزارش صورتحساب‌ها و گزارش تفصیلی واگن‌ها درج می‌شود** |
| مدیریت | صاحبان کالا، نمایندگان (چند صاحب کالا برای هر نماینده)، تعرفه‌ها، درصد مالیات با تاریخچه، کاربران |
| امنیت | ورود با throttle، نقش `admin`/`operator`، غیرفعال‌سازی فوری کاربر، soft delete کاربران، CSRF، اعتبارسنجی فارسی |

---

## پیش‌نیازها

- **PHP 8.2 یا بالاتر** با افزونه‌های:
  `mbstring`، `openssl`، **`pdo_mysql`**، `tokenizer`، `ctype`، `filter`، `session`، و **`dom`** (برای PDF؛ استاندارد در اکثر نصب‌های PHP).
- **MySQL 8.0+** یا **MariaDB 10.6+**
- **Composer 2**
- Node.js فقط در صورت تمایل به بازتولید فایل‌های آفلاین؛ برای اجرا **لازم نیست** (دارایی‌ها از قبل در `public/vendor/` قرار دارند).

> ویندوز: در `php.ini` این خطوط را از حالت comment خارج کنید:
> ```ini
> extension=pdo_mysql
> extension=mbstring
> extension=dom
> ```
> در XAMPP معمولاً `pdo_mysql` و `mbstring` فعال‌اند و فقط `dom` را باید بررسی کنید.

---

## نصب سریع

### ۱) ساخت دیتابیس

در phpMyAdmin (تب SQL) یا با دستور mysql یکی از این دو کار را انجام دهید:

```sql
CREATE DATABASE `wagon_system`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

یا فایل آماده را اجرا کنید:

```bash
mysql -u root -p < database/create_database.sql
```

> اگر می‌خواهید کاربر اختصاصی بسازید، نمونه دستورها داخل همان فایل `database/create_database.sql` نوشته شده است (با رمز دلخواه خودتان).

### ۲) تنظیم `.env`

```bash
cp .env.example .env
```

سپس مقادیر دیتابیس را در `.env` بگذارید:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wagon_system
DB_USERNAME=wagon_user
DB_PASSWORD=رمز-دیتابیس-شما
```

### ۳) نصب و راه‌اندازی

```bash
composer install
composer run setup
php artisan serve
```

سپس در مرورگر: <http://127.0.0.1:8000>

`composer run setup` این کارها را انجام می‌دهد:

1. ساخت `.env` از `.env.example` (اگر وجود نداشته باشد)
2. تولید `APP_KEY`
3. اجرای migrationها (ساخت جدول‌ها در MySQL)
4. اجرای seederها

> دیتابیس باید **از قبل ساخته شده باشد**؛ migration جدول‌ها را می‌سازد، نه خود دیتابیس را. اگر دیتابیس نباشد، پیام «Unknown database» می‌گیرید.

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

اگر بعد از آپلود پروژه صفحه «500 Server Error» می‌بینید، فایل `diagnose.php` را در مرورگر باز کنید:

```
https://دامنه-شما/diagnose.php
```

این صفحه اتصال MySQL، نسخه سرور، جدول‌ها، تعداد کاربران، افزونه‌های PHP، دسترسی پوشه‌ها و `APP_KEY` را بررسی می‌کند و دقیقاً می‌گوید کدام مورد مشکل دارد (بعد از رفع خطا، فایل را حذف کنید).

### ۱) پوشه `vendor` وجود ندارد (شایع‌ترین دلیل)
فایل zip فقط شامل کد پروژه است و پوشه `vendor` داخل آن نیست. روی سرور (یا سیستم خودتان) اجرا کنید:

```bash
composer install
```

اگر Composer روی هاست نیست، از پشتیبانی بخواهید نصب/فعال کند. بدون `vendor`، Laravel حتی یک خطا هم در لاگ ثبت نمی‌کند و فقط صفحه ۵۰۰ می‌دهد.

### ۲) `APP_KEY` خالی است
```bash
php artisan key:generate
```

### ۳) دسترسی نوشتن به `storage` و `bootstrap/cache`
```bash
chmod -R 775 storage bootstrap/cache
```
و مالکیت را به کاربر وب‌سرور بدهید (مثلاً `www-data` یا در cPanel کاربر هاست). روی ویندوز پوشه پروژه نباید Read-only باشد.

### ۴) خطاهای رایج MySQL

| پیام خطا | علت | راه‌حل |
|---|---|---|
| `Unknown database 'wagon_system'` | دیتابیس ساخته نشده | `CREATE DATABASE` را اجرا کنید یا `database/create_database.sql` |
| `Access denied for user` | نام کاربری/رمز یا دسترسی اشتباه | `DB_USERNAME` و `DB_PASSWORD` را در `.env` اصلاح کنید؛ در cPanel به کاربر دیتابیس Privileges بدهید |
| `Connection refused` | MySQL اجرا نیست یا host/port اشتباه است | سرویس MySQL را روشن کنید؛ `DB_HOST=127.0.0.1` و `DB_PORT=3306` را بررسی کنید |
| `could not find driver` | افزونه `pdo_mysql` فعال نیست | در php.ini یا cPanel → PHP Extensions آن را فعال کنید |
| `SQLSTATE[42000] ... Specified key was too long` | MySQL قدیمی (5.6) یا charset اشتباه | از MySQL 8 / MariaDB 10.6 استفاده کنید و `DB_CHARSET=utf8mb4` بگذارید |
| `Too many connections` | محدودیت اتصال هاست | `DB_HOST=localhost` را امتحان کنید یا با پشتیبانی هاست هماهنگ کنید |

#### اگر پیام «نام کاربری یا رمز MySQL درست نیست (Access denied)» دیدید

این صفحه مستقیم از `public/index.php` می‌آید و مقادیر واقعی خوانده‌شده از `.env` را نشان می‌دهد (رمز نمایش داده نمی‌شود، فقط طول آن). به ترتیب این موارد را بررسی کنید:

1. **نام کاربری کامل cPanel:** در cPanel نام کاربر دیتابیس همیشه پیشوند حساب هاست را دارد؛ مثلاً اگر حساب شما `ali` است، کاربر باید `ali_wagon` باشد نه `wagon`. همین مقدار کامل را در `DB_USERNAME` بگذارید.
2. **اضافه‌شدن کاربر به دیتابیس:** در cPanel → **MySQL Databases** → بخش **Add User To Database**، کاربر را انتخاب و به دیتابیس `wagon_system` اضافه کنید و **ALL PRIVILEGES** بدهید. ساخت کاربر به‌تنهایی کافی نیست.
3. **رمزهای خاص:** اگر رمز شامل `#`، فاصله، `$` یا کاراکترهای خاص است، حتماً داخل دابل‌کوتیشن بگذارید:
   ```dotenv
   DB_PASSWORD="p@ss#123 word"
   ```
   بدون کوتیشن، در بعضی سرورها بخش بعد از `#` کامنت می‌شود و رمز ناقص به MySQL می‌رسد.
4. **localhost یا 127.0.0.1:** بعضی هاست‌ها کاربر MySQL را فقط برای `localhost` (سوکت) یا فقط برای `127.0.0.1` (TCP) تعریف می‌کنند. برنامه از نسخه فعلی **هر دو را خودکار امتحان می‌کند**؛ اگر یکی جواب بدهد سایت بالا می‌آید و `diagnose.php` پیشنهاد می‌دهد `DB_HOST` را اصلاح کنید.
5. **کش تنظیمات:** اگر قبلاً `php artisan config:cache` اجرا شده، لاراول `.env` را نمی‌خواند. یک بار اجرا کنید:
   ```bash
   php artisan config:clear
   ```
6. **تغییر رمز از cPanel:** اگر رمز کاربر را در cPanel عوض کردید، همان لحظه `DB_PASSWORD` را هم در `.env` به‌روز کنید (و `config:clear` بزنید).
7. **تست سریع اتصال:** صفحه `diagnose.php` را باز کنید؛ همان اتصال را با همان مقادیر تست می‌کند و خطای دقیق MySQL را نشان می‌دهد. اگر آنجا اتصال «موفق» بود ولی سایت خطا داد، مورد ۵ (کش) را بررسی کنید.

> اگر کاربر به دیتابیس وصل شود ولی اجازه استفاده نداشته باشد، پیام «کاربر MySQL به دیتابیس دسترسی ندارد» (خطای ۱۰۴۴) نمایش داده می‌شود؛ راه‌حل همان مورد ۲ است.

### ۵) جدول‌های دیتابیس ساخته نشده‌اند
```bash
php artisan migrate --seed
```

### ۶) نسخه PHP کمتر از 8.2 است
در cPanel → «Select PHP Version» نسخه 8.2 یا 8.3 را فعال کنید. افزونه‌های `mbstring`، `openssl`، `pdo_mysql` و برای PDF `dom` هم باید فعال باشند.

### ۷) ریشه سایت روی `public` نیست
بهترین حالت: Document Root را روی پوشه `public` بگذارید.
اگر امکان تغییر نیست، فایل `.htaccess` ریشه (که همراه پروژه هست) درخواست‌ها را به `public` هدایت می‌کند؛ فقط باید `mod_rewrite` و `AllowOverride` فعال باشد.

### ۸) دیدن خطای واقعی
در فایل `.env` موقتاً بگذارید:

```dotenv
APP_DEBUG=true
```

بعد صفحه را رفرش کنید یا فایل `storage/logs/laravel.log` را ببینید. بعد از پیدا کردن مشکل حتماً `APP_DEBUG=false` کنید.

### ۹) صفحه بدون استایل (شبیه یک لیست ساده) باز می‌شود

یعنی فایل‌های CSS/JS بارگذاری نشده‌اند. چون همه دارایی‌ها محلی‌اند، این حالت فقط وقتی رخ می‌دهد که فایل‌ها روی سرور نباشند یا آدرسشان اشتباه باشد:

1. این آدرس را در مرورگر باز کنید:
   ```
   https://دامنه-شما/vendor/bootstrap-rtl/bootstrap.rtl.min.css
   ```
   باید متن CSS ببینید. اگر ۴۰۴ بود، یعنی پوشه `public/vendor` آپلود نشده یا DocumentRoot اشتباه است.
2. پوشه‌های `public/vendor` و `public/css` را از بسته کامل پروژه دوباره آپلود کنید (حدود ۶۰۰ کیلوبایت فایل استاتیک).
3. اگر DocumentRoot روی پوشه `public` نیست، باید فایل `.htaccess` ریشه پروژه فعال باشد (پیش‌نیاز: `mod_rewrite` و `AllowOverride`).
4. صفحه `diagnose.php` ردیف **«فایل‌های ظاهری (CSS/JS)»** را بررسی می‌کند و دقیقاً می‌گوید کدام فایل کم است.

> فایل zip این پروژه شامل `public/vendor` هست؛ اگر فقط بخشی از فایل‌ها را جایگزین می‌کنید، این پوشه را جا نیندازید.

### ۱۰) تست مسیر بدون مرورگر
```bash
php -v
php -m | grep pdo_mysql
php artisan --version
php artisan migrate:status
```
اگر `php artisan` اجرا نشد، مشکل در PHP/Composer است، نه در کد برنامه.

---

## نصب دستی (بدون اسکریپت)

```bash
composer install
cp .env.example .env
# مقادیر DB_* را در .env ویرایش کنید
php artisan key:generate
php artisan migrate
php artisan db:seed
```

برای اجرا روی Apache/Nginx، **document root** را روی پوشه `public` تنظیم کنید؛ فایل `public/.htaccess` برای Apache آماده است.

---

## تنظیمات مهم (`.env`)

| کلید | پیش‌فرض | توضیح |
|---|---|---|
| `APP_TIMEZONE` | `Asia/Tehran` | مبنای «امروز»، نمودارها و شماره‌گذاری اسناد |
| `APP_LOCALE` | `fa` | پیام‌های اعتبارسنجی و خطاها فارسی |
| `DB_CONNECTION` | `mysql` | نوع دیتابیس |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` | مشخصات سرور دیتابیس |
| `DB_DATABASE` | `wagon_system` | نام دیتابیس (باید از قبل ساخته شود) |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / خالی | کاربر دیتابیس |
| `DB_SOCKET` | خالی | اگر MySQL روی سوکت یونیکس اجرا می‌شود |
| `DB_CHARSET` / `DB_COLLATION` | `utf8mb4` / `utf8mb4_unicode_ci` | برای نمایش صحیح فارسی و ایموجی |
| `DB_FOREIGN_KEYS` | `true` | فقط برای SQLite کاربرد دارد |
| `SESSION_DRIVER` | `file` | بدون نیاز به جدول session |
| `CACHE_STORE` | `file` | ذخیره throttle ورود |
| `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` | — | فقط هنگام seed خوانده می‌شوند (رمز کوتاه‌تر از ۱۰ کاراکتر رد می‌شود) |
| `SESSION_HTTP_ONLY` / `SESSION_SAME_SITE` | `true` / `lax` | محافظت کوکی نشست |
| `SESSION_SECURE_COOKIE` | `false` | **روی HTTPS حتماً `true`** |
| `TRUSTED_PROXIES` | خالی | IP پروکسی معکوس؛ فقط پشت nginx/CDN پر شود |
| `DIAGNOSE_KEY` | خالی | کلید باز کردن `diagnose.php` از راه دور |

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
  create_database.sql       ساخت دیتابیس MySQL و نمونه کاربر
  migrations/               جداول سیستم + users/sessions/cache/jobs
  seeders/                  کاربر ادمین/اپراتور، تعرفه‌های پایه، درصد مالیات
lang/fa/                    validation, auth, passwords, pagination
public/
  index.php (بررسی پیش‌نیازها، اتصال MySQL و پیام فارسی)، .htaccess
  css/app.css
  vendor/                   Bootstrap 5 RTL، jQuery، datepicker، Chart.js، فونت وزیرمتن
resources/views/            ۳۳ نمای Blade (layouts، auth، dashboard، cargo، billing،
                            owners، representatives، tariffs، tax، users، reports)
routes/web.php              تمام مسیرهای وب
.htaccess                   هدایت به public برای هاست‌هایی که document root را
                            نمی‌شود تغییر داد
diagnose.php                ابزار عیب‌یابی پیش‌نیازها و اتصال MySQL (بعد از رفع خطا حذف شود)
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

تست‌ها با **SQLite در حافظه** اجرا می‌شوند (`phpunit.xml`) تا به سرور MySQL نیاز نداشته باشند و دیتابیس اصلی هم دست‌نخورده بماند. migrationها به‌صورت سازگار با هر دو موتور نوشته شده‌اند، بنابراین این تست‌ها همان رفتار MySQL را پوشش می‌دهند.

پوشش فعلی:

- `tests/Unit/JalaliDateTest.php` — نرمال‌سازی ارقام فارسی/عربی، اعتبار تاریخ شمسی، کبیسه
- `tests/Feature/BillingFlowTest.php` — چرخه کامل درخواست → بارگیری → صورتحساب → لغو و آزادسازی
- `tests/Feature/ValidationTest.php` — تاریخ نامعتبر، ارقام فارسی، ظرفیت، واگن تکراری، نماینده نامرتبط
- `tests/Feature/AccessControlTest.php` — مهمان/اپراتور/ادمین، کاربر غیرفعال، محافظت از حساب خود
- `tests/Feature/ReportsTest.php` — جمع کل گزارش مستقل از صفحه‌بندی و تاریخ شمسی

> اگر می‌خواهید تست‌ها روی MySQL اجرا شوند، در `phpunit.xml` مقادیر `DB_CONNECTION`/`DB_DATABASE` را عوض کنید و یک دیتابیس تست جدا (مثلاً `wagon_system_test`) بسازید. تست‌ها با `RefreshDatabase` همه جدول‌ها را خالی می‌کنند، پس هرگز روی دیتابیس اصلی اجرا نکنید.

---

## PDF صورتحساب

- فونت `Vazirmatn` از `storage/fonts/*.ttf` با `@font-face` و `file://` بارگذاری می‌شود؛ نیازی به دسترسی اینترنت نیست.
- چیدمان کاملاً RTL است و `isPhpEnabled=false` قرار گرفته است.
- پوشه `storage/fonts` باید قابل نوشتن باشد (Dompdf کش فونت می‌سازد).
- برای تولید PDF، افزونه `dom` الزامی است. اگر این افزونه روی سرور فعال نباشد، به‌جای خطای ۵۰۰ همان فاکتور به‌صورت **HTML قابل چاپ** نمایش داده می‌شود و با Ctrl+P می‌توانید آن را به PDF ذخیره کنید.

---

## پشتیبان‌گیری و نگهداری

### بک‌آپ MySQL

```bash
mysqldump -u wagon_user -p --single-transaction --routines --triggers \
    wagon_system > backup-$(date +%F).sql
```

بازیابی:

```bash
mysql -u wagon_user -p wagon_system < backup-2026-10-17.sql
```

- در cPanel از بخش **phpMyAdmin → Export** هم می‌توانید بک‌آپ بگیرید.
- فایل `.env` و `storage/logs/` را در مخزن عمومی قرار ندهید (در `.gitignore` هستند).
- رمزها با bcrypt هش می‌شوند؛ رمز اولیه را بعد از اولین ورود تغییر دهید.
- برای محیط عملیاتی `APP_DEBUG=false` و HTTPS (به همراه `SESSION_SECURE_COOKIE=true`) توصیه می‌شود.
- اگر خواستید داده‌ها را از SQLite قبلی منتقل کنید، از ابزارهایی مثل `mysqlworkbench` یا اسکریپت مهاجرت سفارشی استفاده کنید؛ در این نسخه ابزار خودکار انتقال داده وجود ندارد.

---

## محدودیت‌های شناخته‌شده

- **Audit Log کامل** (ثبت تاریخچه تغییرات تعرفه/مالیات/فاکتور) پیاده‌سازی نشده است؛ فقط تاریخچه درصد مالیات و snapshotهای فاکتور وجود دارد.
- چرخه پرداخت (پرداخت‌شده/تسویه‌شده) و گزارش‌های پرداخت وجود ندارد؛ لغو فاکتور، واگن‌ها را آزاد می‌کند.
- محاسبات مالی به ریال و با `round` انجام می‌شود؛ برای واحدهای پولی با اعشار، بهتر است محاسبات در «ریال × ۱۰۰» انجام شود.
- SQLite همچنان به‌عنوان حالت جایگزین (فقط برای تست و اجرای محلی) پشتیبانی می‌شود، اما محیط عملیاتی باید MySQL/MariaDB باشد.
- تمایل به ارقام فارسی در همه جدول‌ها وجود دارد اما خروجی‌ها با ارقام لاتین و جداکننده هزارگان نمایش داده می‌شوند.

---

## امنیت

بازبینی امنیتی کامل (احراز هویت، مجوزدهی، CSRF، XSS، SQL Injection، نشست/کوکی، هدرها، سیاست رمز، rate limit و فایل‌های نصب) در فایل [`SECURITY.md`](SECURITY.md) ثبت شده است. خلاصه وضعیت:

- هدرهای امنیتی (`CSP`، `nosniff`، `Referrer-Policy`، `Permissions-Policy`، HSTS در production) روی همه پاسخ‌ها فعال است.
- `diagnose.php` فقط از لوکال‌هاست یا با `DIAGNOSE_KEY` باز می‌شود؛ **بعد از نصب حذفش کنید**.
- صفحه خطای راه‌اندازی در حالت production هیچ جزئیات فنی (نام کاربر دیتابیس، مسیر، Exception) نشان نمی‌دهد.
- سیاست رمز: حداقل ۱۰ کاراکتر شامل حرف و رقم.
- پیش از تحویل، چک‌لیست بخش ۶ فایل `SECURITY.md` را اجرا کنید (به‌ویژه `SESSION_SECURE_COOKIE=true` روی HTTPS و تغییر رمز ادمین نمونه).

---

## مستند بررسی و اصلاحات

فایل [`REVIEW.md`](REVIEW.md) گزارش کامل بررسی نسخه اولیه (باگ‌ها، امنیت، UI، تناقض‌های README) و **وضعیت اصلاح هر مورد** را در ابتدای فایل دارد. خلاصه: تمام موارد مسدودکننده (P0) و باگ‌های مالی/داده‌ای (P1) برطرف شده‌اند؛ موارد کم‌اهمیت‌تر یا خارج از دامنه (مانند Audit Log) در همان فایل با دلیل مشخص شده‌اند.
