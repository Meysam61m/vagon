# گزارش بازبینی امنیتی — سامانه مدیریت بارگیری واگن

**تاریخ بازبینی:** ۲۵ مهر ۱۴۰۴ (۲۰۲۶-۱۰-۱۷)
**نسخه بررسی‌شده:** آخرین نسخه شاخه `arena/d3f3af60-vagon`
**دامنه:** کل برنامه (احراز هویت، مجوزدهی، فرم‌ها، گزارش‌ها، فایل‌های نصب و پیکربندی)

---

## ۱) خلاصه اجرایی

برنامه از نظر کلاس‌های رایج آسیب‌پذیری وب (XSS، CSRF، SQL Injection، اجرای کد از راه دور، کنترل دسترسی) **پاک** بود؛ تنها خلأ جدی، **نبود هدرهای امنیتی مرورگر** و **افشای اطلاعات فنی در دو مسیر** (صفحه خطای راه‌اندازی و `diagnose.php`) بود که در همین بازبینی برطرف شد.

| نتیجه | تعداد |
|---|---|
| آسیب‌پذیری بحرانی (قابل نفوذ از اینترنت بدون حساب کاربری) | ۰ |
| یافته بالا (اصلاح‌شده) | ۳ |
| یافته متوسط (اصلاح‌شده) | ۴ |
| یافته پایین (اصلاح‌شده / پذیرفته‌شده) | ۴ |
| کنترل‌های امنیتی که درست پیاده‌سازی شده بودند | ۱۲ |

> **جمع‌بندی:** پس از اعمال اصلاحات این گزارش، ریسک امنیتی برنامه برای استقرار داخلی (intranet) در سطح قابل‌قبول است. پیش از استقرار عمومی، چک‌لیست بخش ۶ را کامل اجرا کنید.

---

## ۲) یافته‌ها و وضعیت اصلاح

| # | حوزه | یافته | شدت | وضعیت |
|---|---|---|---|---|
| ۱ | هدرهای HTTP | هیچ هدر امنیتی ارسال نمی‌شد (بدون CSP، nosniff، Referrer-Policy) | بالا | ✅ اصلاح شد |
| ۲ | افشای اطلاعات | صفحه خطای راه‌اندازی در `public/index.php` نام کاربر دیتابیس، مسیر فایل‌ها، کلاس/پیام Exception و جزئیات `GRANT` را نشان می‌داد | بالا | ✅ اصلاح شد |
| ۳ | کنترل دسترسی | `diagnose.php` برای همه باز بود و نسخه PHP، افزونه‌ها، مسیرها و وضعیت دیتابیس را افشا می‌کرد | بالا | ✅ اصلاح شد |
| ۴ | رمز عبور | سیاست رمز فقط `min:8` بود؛ رمزهایی مثل `abcdefgh` پذیرفته می‌شد | متوسط | ✅ اصلاح شد |
| ۵ | پیام‌های خطا | پیام قواعد `Password` به انگلیسی نمایش داده می‌شد | پایین | ✅ اصلاح شد |
| ۶ | نرمال‌سازی ورودی | ایمیل با حروف بزرگ (`ADMIN@...`) وارد می‌شد و رفتار غیرقابل‌پیش‌بینی ایجاد می‌کرد | پایین | ✅ اصلاح شد |
| ۷ | پروکسی معکوس | `trusted_proxies` به‌صورت پیش‌فرض همه پروکسی‌ها را قبول می‌کرد (قابل جعل IP در throttle) | متوسط | ✅ اصلاح شد |
| ۸ | رمز اولیه | `ResetAdminPassword` و Seeder طول رمز را بررسی نمی‌کردند | متوسط | ✅ اصلاح شد |
| ۹ | پیکربندی وب‌سرور | بدون `.htaccess` سخت‌شده، `.env`/`.git`/`composer.*` قابل دانلود بود | متوسط | ✅ اصلاح شد |
| ۱۰ | کوکی نشست | `Secure` مستند نشده بود؛ `HttpOnly`/`SameSite` درست بود | پایین | ✅ اصلاح شد (`.env.example`) |
| ۱۱ | CSP | `script-src 'unsafe-inline'` لازم است (اسکریپت‌های درون‌خطی datepicker و چاپ) | پایین | ⚠️ پذیرفته‌شده |
| ۱۲ | احراز هویت | ۲FA / قفل حساب پس از N تلاش ناموفق وجود ندارد (فقط rate limit) | پایین | ⚠️ خارج از دامنه |
| ۱۳ | ثبت رویداد | Audit Log تغییرات مدیریتی وجود ندارد | اطلاعاتی | ⚠️ خارج از دامنه |
| ۱۴ | Rate limit | شمارنده روی `file cache` است و در چند سرور مشترک نیست | اطلاعاتی | ⚠️ برای نصب تک‌سروری کافی است |

---

## ۳) جزئیات اصلاحات

### ۳-۱. میدل‌ور `SecurityHeaders`

فایل `app/Http/Middleware/SecurityHeaders.php` ساخته و در همه مسیرهای `web` ثبت شد (`bootstrap/app.php`). هدرهای ارسالی در **همه** پاسخ‌ها (از جمله ورود، داشبورد و خطاها):

```
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()
X-Permitted-Cross-Domain-Policies: none
X-Robots-Tag: noindex, nofollow
Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none';
  img-src 'self' data:; font-src 'self' data:; style-src 'self' 'unsafe-inline';
  script-src 'self' 'unsafe-inline'; connect-src 'self'; form-action 'self';
  frame-ancestors 'self'
```

در محیط `production` علاوه بر این‌ها:

```
X-Frame-Options: SAMEORIGIN
Strict-Transport-Security: max-age=31536000; includeSubDomains   (فقط روی HTTPS)
```

در محیط `local` مقدار `frame-ancestors *` است تا پیش‌نمایش داخل iframe کار کند؛ این تفاوت **عمدی** است و در سرور واقعی اعمال نمی‌شود.

این هدرها روی **صفحه‌های خطا** هم اعمال می‌شوند (با `$exceptions->respond()` در `bootstrap/app.php`)، چون میدل‌ور وب روی پاسخ‌هایی مثل `404`، `419` و `500` اجرا نمی‌شود. آزمون‌شده: `404`، `403` و `419` همگی `Content-Security-Policy` و `X-Content-Type-Options` دارند.

### ۳-۲. پنهان‌سازی جزئیات خطا

`public/index.php` بازنویسی شد:

- جزئیات فنی (نام کاربر DB، مسیرهای امتحان‌شده، کلاس و پیام Exception، دستورات `GRANT`/ساخت schema) فقط وقتی نمایش داده می‌شود که `APP_DEBUG=true` **یا** `APP_ENV=local` باشد.
- در بقیه حالت‌ها فقط یک صفحه فارسی با راهنمای رفع خطا نمایش داده می‌شود و اصل خطا در `storage/logs/laravel.log` ثبت می‌شود.
- خطاهای PDO به پیام‌های فارسی قابل‌فهم (`could not find driver`، `Access denied`، `Unknown database`) تبدیل می‌شوند، بدون افشای نام کاربر یا نام دیتابیس.

**آزمون:** با `APP_ENV=production` و خطای عمدی، صفحه فقط پیام «جزئیات فنی خطا برای امنیت نمایش داده نمی‌شود» را برگرداند و هیچ مسیر یا کلاس PHP در خروجی نبود.

### ۳-۳. قفل دسترسی `diagnose.php`

- اگر `REMOTE_ADDR` یکی از `127.0.0.1` / `::1` باشد → باز است (برای عیب‌یابی از SSH).
- در غیر این صورت فقط با `?key=...` یا هدر `X-Diagnose-Key` برابر مقدار `DIAGNOSE_KEY` در `.env` باز می‌شود (مقایسه با `hash_equals`).
- در غیر این صورت صفحه ۴۰۳ فارسی با `noindex` و `no-store` برمی‌گردد.

**آزمون‌های انجام‌شده:**

| درخواست | نتیجه |
|---|---|
| بدون کلید از IP عمومی | ۴۰۳ فارسی، بدون هیچ خروجی فنی |
| با کلید اشتباه | ۴۰۳ فارسی |
| با `DIAGNOSE_KEY` درست | ۲۰۰ + گزارش کامل (نسخه PHP، افزونه‌ها، دیتابیس) |
| از `127.0.0.1` بدون کلید | ۲۰۰ (bypass لوکال) |

### ۳-۴. سیاست رمز عبور

در `UserController` (ساخت و ویرایش کاربر) و `ResetAdminPassword`:

```php
Password::min(10)->letters()->numbers()
```

به‌همراه `confirmed` و پیام‌های فارسی جدید در `lang/fa/validation.php` برای قواعد `letters`/`numbers`/`mixed`/`symbols`/`uncompromised`.

**آزمون واقعی:**

- رمز `short1` → «رمز عبور باید حداقل ۱۰ کاراکتر باشد.»
- رمز `abcdefghij` → «رمز عبور باید حداقل یک عدد داشته باشد.»
- رمز `StrongPass1404` → کاربر ساخته شد و ایمیل به‌صورت `strong.test@local` ذخیره شد.

### ۳-۵. نرمال‌سازی ایمیل

در `AuthController` پیش از کلید throttle و پیش از `Auth::attempt`، و در `UserController` هنگام ذخیره:

```php
mb_strtolower(trim($email))
```

**آزمون:** ورود با `ADMIN@WAGON.LOCAL` → `302` به `/dashboard`؛ ورود با `OPERATOR@WAGON.LOCAL` → `302` به `/dashboard`.

> نکته: در آزمون اولیه، ورود با حروف بزرگ ناموفق بود؛ علت **مصرف شدن سهمیه rate limit** (۵ تلاش در دقیقه) توسط آزمون‌های قبلی بود، نه باگ کد. پس از پنجره ۶۰ ثانیه‌ای، ورود با حروف بزرگ درست کار کرد.

### ۳-۶. پروکسی معکوس و IP واقعی

- `config/app.php` → `trusted_proxies` از `TRUSTED_PROXIES` خوانده می‌شود (پیش‌فرض خالی).
- `AppServiceProvider` در صورت مقداردهی، `Request::setTrustedProxies()` را با هدرهای `X-Forwarded-For/Host/Port/Proto` تنظیم می‌کند.
- مقدار `*` به `0.0.0.0/0` و `::/0` ترجمه می‌شود (فقط اگر واقعاً پشت پروکسی مطمئن هستید).

### ۳-۷. سخت‌سازی وب‌سرور (`.htaccess`)

`.htaccess` ریشه و `public/.htaccess`:

- `Options -Indexes` (با `IfModule` تا روی هاست‌های اشتراکی خطا ندهد)
- مسدودسازی `.env`، `.git`، `composer.json/lock`، `artisan`، `*.sql`، `*.log`، `*.bak` (به‌جز `.well-known`)
- `Deny from all` برای پوشه‌های `app/`، `config/`، `database/`، `resources/`، `routes/`، `storage/`، `tests/`، `vendor/` در داکیومنت‌روت اشتباه
- هدرهای پایه امنیتی برای هاست‌هایی که خودشان هدر نمی‌فرستند

> عمداً از `Header unset Server` و `php_flag` استفاده نشده است؛ روی هاست‌های اشتراکی باعث خطای ۵۰۰ می‌شوند.

### ۳-۸. نشست و کوکی

پیش‌فرض‌ها در `.env.example`:

```dotenv
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
# روی HTTPS حتماً فعال کنید:
# SESSION_SECURE_COOKIE=true
```

**آزمون:** کوکی نشست `HttpOnly; SameSite=Lax` است؛ در ورود شناسه نشست عوض می‌شود (session fixation مسدود)؛ خروج، نشست را باطل می‌کند و داشبورد دیگر باز نمی‌شود.

---

## ۴) کنترل‌هایی که بررسی و سالم تشخیص داده شدند

| کنترل | روش بررسی | نتیجه |
|---|---|---|
| XSS | جست‌وجوی `{!! !!}` و خروجی خام در همه Bladeها | هیچ موردی نبود؛ همه خروجی‌ها escape می‌شوند |
| CSRF | وجود `@csrf` در همه فرم‌ها + POST بدون توکن | `419` (رد شد) |
| SQL Injection | همه `DB::raw`/`selectRaw`/`whereRaw` | همگی ثابت و بدون ورودی کاربر |
| Mass Assignment | `$fillable` همه مدل‌ها | محدود؛ `role`/`is_active` فقط از مسیر ادمین |
| کنترل دسترسی | ورود با حساب `operator` و درخواست مسیرهای مدیریتی | `403` برای `/users`, `/tariffs`, `/owners`, `/tax` |
| Session Fixation | مقایسه کوکی پیش/پس از ورود | شناسه عوض شد |
| User Enumeration | پیام خطای ورود برای ایمیل ناموجود و رمز غلط | پیام یکسان: «ایمیل یا رمز عبور اشتباه است.» |
| اجرای کد | جست‌وجوی `eval`/`exec`/`shell_exec`/`system` | هیچ موردی نبود |
| آپلود فایل | جست‌وجوی `UploadedFile`/`->file()` | قابلیت آپلود وجود ندارد |
| SSRF | جست‌وجوی `Http::`/`file_get_contents` با URL | فراخوانی خارجی وجود ندارد |
| رمزها | مدل `User` | `hashed` cast + `$hidden = ['password','remember_token']` |
| افشای رمز seed | `UserSeeder` | رمز تصادفی ۱۲ کاراکتری؛ رمز ضعیف از `.env` رد می‌شود |

---

## ۵) رفتار rate limit (برای اطلاع ادمین)

- مسیر `POST /login` دو لایه محدودیت دارد: `throttle:5,1` (لاراول) و شمارنده دستی `RateLimiter` با کلید «ایمیل نرمال‌شده + IP»، پنجره ۶۰ ثانیه.
- پس از ۵ تلاش ناموفق، پیام «تلاش‌های ناموفق زیاد بود؛ لطفاً N ثانیه دیگر…» نمایش داده می‌شود.
- ورود موفق شمارنده را پاک می‌کند.
- شمارنده در `CACHE_STORE=file` ذخیره می‌شود؛ برای نصب چندسروری، `CACHE_STORE=redis` توصیه می‌شود.

---

## ۶) چک‌لیست استقرار امن (قبل از تحویل به کاربر نهایی)

1. **حالت تولید:**
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   ```
2. **کوکی امن روی HTTPS:**
   ```dotenv
   SESSION_SECURE_COOKIE=true
   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax
   ```
3. **پروکسی معکوس** (اگر پشت nginx/CDN هستید):
   ```dotenv
   TRUSTED_PROXIES=IP-nginx-or-cdn
   ```
   اگر مستقیم روی اینترنت هستید، این مقدار را **خالی** بگذارید.
4. **`diagnose.php`:** یا `DIAGNOSE_KEY=<رشته تصادفی طولانی>` بگذارید، یا پس از نصب فایل را **حذف** کنید.
5. **رمز ادمین اولیه را عوض کنید:**
   ```bash
   php artisan admin:reset-password admin@example.com
   ```
6. **کاربر دیتابیس با حداقل دسترسی:** فقط `SELECT, INSERT, UPDATE, DELETE` روی دیتابیس برنامه؛ بدون `GRANT`/`DROP`/`FILE`.
7. **HTTPS اجباری** و در صورت امکان HSTS (میدل‌ور در production روی درخواست امن خودکار ارسال می‌کند).
8. **مجوز فایل‌ها:**
   ```bash
   chmod 600 .env
   chmod -R 775 storage bootstrap/cache
   ```
9. **کش تولید:**
   ```bash
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
10. **پشتیبان‌گیری** روزانه از دیتابیس و پوشه `storage/app` (بخش پشتیبان‌گیری README).
11. **پایش لاگ:** `storage/logs/laravel.log` را دوره‌ای بررسی کنید؛ خطاهای ورود زیاد نشانه حمل brute-force است.
12. **`vendor/` را از طریق Composer نصب کنید** (`composer install --no-dev --optimize-autoloader`) و از کپی دستی پرهیز کنید.

---

## ۷) ریسک‌های پذیرفته‌شده و پیشنهادهای آینده

| مورد | دلیل پذیرش | پیشنهاد آینده |
|---|---|---|
| `script-src 'unsafe-inline'` در CSP | datepicker و دکمه چاپ از اسکریپت درون‌خطی استفاده می‌کنند | انتقال اسکریپت‌ها به فایل `.js` و استفاده از nonce |
| نبود ۲FA | سامانه داخلی با تعداد کاربر محدود | افزودن TOTP برای نقش ادمین |
| نبود Audit Log | خارج از دامنه این بازبینی (در `REVIEW.md` هم ذکر شده) | جدول `activity_log` برای تغییرات تعرفه/مالیات/کاربر |
| rate limit روی فایل | نصب تک‌سروری | Redis در استقرار چندسروری |
| بدون انقضای رمز عبور | سیاست سازمانی | اجبار تغییر رمز هر ۹۰ روز |

---

## ۸) بازآزمایی سریع (برای ادمین)

```bash
# هدرهای امنیتی
curl -sI https://your-domain.example/login | grep -iE "content-security|x-content-type|referrer|permissions|x-robots"

# diagnose باید ۴۰۳ بدهد (بدون کلید)
curl -s -o /dev/null -w "%{http_code}\n" https://your-domain.example/diagnose.php

# دسترسی فایل‌های حساس باید ۴۰۳/۴۰۴ بدهد
curl -s -o /dev/null -w "%{http_code}\n" https://your-domain.example/.env
curl -s -o /dev/null -w "%{http_code}\n" https://your-domain.example/.git/config

# POST بدون CSRF باید ۴۱۹ بدهد
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://your-domain.example/login -d "email=a@b.c&password=x"
```

---

## ۹) پیوست: فایل‌های تغییریافته در این بازبینی

| فایل | تغییر |
|---|---|
| `app/Http/Middleware/SecurityHeaders.php` | جدید — هدرهای امنیتی |
| `bootstrap/app.php` | ثبت میدل‌ور + alias نقش |
| `config/app.php` | `trusted_proxies` |
| `app/Providers/AppServiceProvider.php` | تنظیم پروکسی‌های مطمئن |
| `public/index.php` | پنهان‌سازی جزئیات خطا در حالت تولید |
| `diagnose.php` | قفل کلید/لوکال‌هاست |
| `app/Http/Controllers/AuthController.php` | نرمال‌سازی ایمیل |
| `app/Http/Controllers/UserController.php` | سیاست رمز + نرمال‌سازی ایمیل |
| `app/Console/Commands/ResetAdminPassword.php` | سیاست رمز |
| `database/seeders/UserSeeder.php` | رد رمز ضعیف |
| `lang/fa/validation.php` | پیام‌های فارسی قواعد رمز |
| `.env.example` | مستندسازی نشست/پروکسی/کلید عیب‌یابی |
| `.htaccess` و `public/.htaccess` | سخت‌سازی وب‌سرور |
