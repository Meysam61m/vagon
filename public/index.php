<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$wagonBase = dirname(__DIR__);

/*
|--------------------------------------------------------------------------
| بررسی‌های پیش از بوت لاراول
|--------------------------------------------------------------------------
| اگر یکی از پیش‌نیازها آماده نباشد، به‌جای صفحه ۵۰۰ خالی یک پیام فارسی
| با راه‌حل نمایش داده می‌شود. این بخش عمداً سینتکس ساده (سازگار با PHP 7)
| دارد تا حتی روی نسخه‌های قدیمی PHP هم پیام قابل فهم بدهد.
*/
$wagonFatal = function ($title, $hints) {
    if (! headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $items = '';

    foreach ((array) $hints as $hint) {
        $items .= '<li>'.$hint.'</li>';
    }

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
        .'<meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<title>خطای راه‌اندازی</title><style>'
        .'body{font-family:Tahoma,Arial,sans-serif;background:#f7f7f8;margin:0;padding:32px;color:#111}'
        .'.box{max-width:780px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px 30px}'
        .'h1{font-size:19px;margin:0 0 14px;color:#b91c1c;line-height:1.7}'
        .'code{background:#f3f4f6;padding:2px 6px;border-radius:4px;direction:ltr;display:inline-block;font-family:Consolas,monospace}'
        .'li{margin-bottom:9px;line-height:1.9}'
        .'.muted{color:#6b7280;font-size:13px;margin-top:20px;line-height:1.9;border-top:1px solid #eee;padding-top:14px}'
        .'</style></head><body><div class="box"><h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>'
        .'<ul>'.$items.'</ul>'
        .'<p class="muted">این پیام از <code>public/index.php</code> می‌آید و جایگزین صفحه ۵۰۰ خالی است. '
        .'برای بررسی کامل، آدرس <code>diagnose.php</code> را در ریشه پروژه باز کنید.</p>'
        .'</div></body></html>';

    exit;
};

if (PHP_VERSION_ID < 80200) {
    $wagonFatal('نسخه PHP قدیمی است (نسخه فعلی: '.PHP_VERSION.')', [
        'این برنامه به <strong>PHP 8.2 یا بالاتر</strong> نیاز دارد.',
        'در cPanel از بخش «Select PHP Version» نسخه 8.2 یا 8.3 را انتخاب و فعال کنید.',
        'در XAMPP نسخه 8.2 به بالا نصب کنید.',
    ]);
}

$wagonAutoload = $wagonBase.'/vendor/autoload.php';

if (! is_file($wagonAutoload)) {
    $wagonFatal('وابستگی‌های Composer نصب نشده‌اند (پوشه vendor وجود ندارد)', [
        'در پوشه پروژه دستور <code>composer install</code> را اجرا کنید.',
        'فایل zip پروژه فقط شامل کد است؛ پوشه <code>vendor</code> باید روی سرور ساخته شود.',
        'اگر Composer نصب نیست، از <code>getcomposer.org</code> نصبش کنید یا از پشتیبانی هاست بخواهید.',
        'حداقل یک بار هم باید <code>php artisan migrate --seed</code> اجرا شود تا جداول و کاربر ادمین ساخته شوند.',
    ]);
}

$wagonEnvPath = $wagonBase.'/.env';
$wagonEnv = is_file($wagonEnvPath) ? @file_get_contents($wagonEnvPath) : null;
$wagonAppKey = getenv('APP_KEY');

if ($wagonAppKey === false && is_string($wagonEnv) && preg_match('/^\s*APP_KEY\s*=\s*(.*)$/m', $wagonEnv, $wagonMatch)) {
    $wagonAppKey = trim($wagonMatch[1], " \t\"'");
}

if (! is_file($wagonEnvPath) && ($wagonAppKey === false || $wagonAppKey === '')) {
    $wagonFatal('فایل .env وجود ندارد', [
        'فایل <code>.env.example</code> را به <code>.env</code> کپی کنید (ویندوز: کپی و تغییر نام).',
        'سپس دستور <code>php artisan key:generate</code> را اجرا کنید.',
        'روی هاست اشتراکی معمولاً می‌توانید فایل را از File Manager بسازید و محتوای <code>.env.example</code> را در آن بگذارید.',
    ]);
}

if ($wagonAppKey === false || $wagonAppKey === '') {
    $wagonFatal('کلید برنامه (APP_KEY) تنظیم نشده است', [
        'دستور <code>php artisan key:generate</code> را در پوشه پروژه اجرا کنید.',
        'یا در فایل <code>.env</code> مقدار <code>APP_KEY=</code> را با یک کلید معتبر پر کنید.',
        'تا وقتی APP_KEY خالی باشد، ورود، سشن و CSRF کار نمی‌کنند و صفحه ۵۰۰ می‌بینید.',
    ]);
}

// پوشه‌های لازم: در صورت نبود ساخته می‌شوند و بعد دسترسی نوشتن بررسی می‌شود
$wagonDirs = [
    'storage',
    'storage/app',
    'storage/app/private',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
    'storage/fonts',
    'bootstrap/cache',
];

$wagonNotWritable = [];

foreach ($wagonDirs as $wagonDir) {
    $wagonPath = $wagonBase.'/'.$wagonDir;

    if (! is_dir($wagonPath)) {
        @mkdir($wagonPath, 0775, true);
    }

    if (! is_dir($wagonPath) || ! is_writable($wagonPath)) {
        $wagonNotWritable[] = $wagonDir;
    }
}

if ($wagonNotWritable) {
    $wagonFatal('پوشه‌های storage / bootstrap/cache قابل نوشتن نیستند', [
        'این پوشه‌ها باید برای وب‌سرور قابل نوشتن باشند: <code>'.implode('</code> ، <code>', $wagonNotWritable).'</code>',
        'روی لینوکس: <code>chmod -R 775 storage bootstrap/cache</code> و مالکیت را به کاربر وب‌سرور بدهید '
            .'(مثلاً <code>chown -R www-data:www-data storage bootstrap/cache</code>).',
        'روی cPanel از File Manager → Permissions مقدار 755 یا 775 را برای این پوشه‌ها تنظیم کنید.',
        'روی ویندوز پوشه پروژه نباید فقط-خواندنی (Read-only) باشد.',
    ]);
}

// ---------------------------------------------------------------------------
// بررسی دیتابیس (پیش‌فرض: MySQL / MariaDB)
// ---------------------------------------------------------------------------
$wagonEnvValue = function (string $key, ?string $default = null) use ($wagonEnv) {
    if (is_string($wagonEnv) && preg_match('/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)$/m', $wagonEnv, $m)) {
        $value = trim($m[1], " \t\"'");

        return $value === '' ? $default : $value;
    }

    $env = getenv($key);

    return ($env === false || $env === '') ? $default : $env;
};

$wagonDbDriver = strtolower((string) $wagonEnvValue('DB_CONNECTION', 'mysql'));
$wagonDbName = (string) $wagonEnvValue('DB_DATABASE', 'wagon_system');

if (in_array($wagonDbDriver, ['mysql', 'mariadb'], true)) {
    if (! extension_loaded('pdo_mysql')) {
        $wagonFatal('افزونه pdo_mysql روی PHP فعال نیست', [
            'دیتابیس برنامه MySQL/MariaDB است و PHP به افزونه <code>pdo_mysql</code> نیاز دارد.',
            'در cPanel → «Select PHP Version» → Extensions گزینه <code>pdo_mysql</code> (و <code>mysqli</code>) را فعال کنید.',
            'در ویندوز/لینوکس داخل php.ini خط <code>extension=pdo_mysql</code> را از حالت comment خارج کنید و وب‌سرور را ری‌استارت کنید.',
            'با دستور <code>php -m</code> بررسی کنید که <code>pdo_mysql</code> در فهرست باشد.',
        ]);
    }

    $wagonDbHost = (string) $wagonEnvValue('DB_HOST', '127.0.0.1');
    $wagonDbPort = (string) $wagonEnvValue('DB_PORT', '3306');
    $wagonDbUser = (string) $wagonEnvValue('DB_USERNAME', 'root');
    $wagonDbPass = (string) $wagonEnvValue('DB_PASSWORD', '');
    $wagonDbSocket = (string) $wagonEnvValue('DB_SOCKET', '');

    $wagonDsn = $wagonDbSocket !== ''
        ? 'mysql:unix_socket='.$wagonDbSocket.';dbname='.$wagonDbName.';charset=utf8mb4'
        : 'mysql:host='.$wagonDbHost.';port='.$wagonDbPort.';dbname='.$wagonDbName.';charset=utf8mb4';

    try {
        $wagonPdo = new PDO($wagonDsn, $wagonDbUser, $wagonDbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        $wagonTableCount = (int) $wagonPdo->query(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchColumn();

        if ($wagonTableCount === 0) {
            $wagonFatal('اتصال به MySQL برقرار شد، اما دیتابیس خالی است', [
                'دیتابیس <code>'.htmlspecialchars($wagonDbName, ENT_QUOTES, 'UTF-8').'</code> ساخته شده اما هیچ جدولی ندارد.',
                'دستور <code>php artisan migrate --seed</code> را در پوشه پروژه اجرا کنید.',
            ]);
        }
    } catch (PDOException $wagonDbException) {
        $wagonCode = (int) ($wagonDbException->errorInfo[1] ?? 0);
        $wagonMessage = $wagonDbException->getMessage();

        if ($wagonCode === 1049 || strpos($wagonMessage, 'Unknown database') !== false) {
            $wagonFatal('دیتابیس «'.$wagonDbName.'» وجود ندارد', [
                'در phpMyAdmin یا MySQL دستور زیر را اجرا کنید:',
                '<code>CREATE DATABASE `'.htmlspecialchars($wagonDbName, ENT_QUOTES, 'UTF-8').'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code>',
                'یا فایل <code>database/create_database.sql</code> را در phpMyAdmin اجرا کنید.',
                'سپس <code>php artisan migrate --seed</code> را اجرا کنید.',
            ]);
        }

        if ($wagonCode === 1045 || strpos($wagonMessage, 'Access denied') !== false) {
            $wagonFatal('نام کاربری یا رمز MySQL درست نیست (Access denied)', [
                'مقادیر <code>DB_USERNAME</code> و <code>DB_PASSWORD</code> را در <code>.env</code> بررسی کنید.',
                'در cPanel معمولاً نام کاربری با پیشوند حساب هاست شروع می‌شود (مثلاً <code>user_wagon</code>).',
                'مطمئن شوید کاربر دیتابیس به دیتابیس <code>'.htmlspecialchars($wagonDbName, ENT_QUOTES, 'UTF-8').'</code> دسترسی (Privileges) دارد.',
            ]);
        }

        if (in_array($wagonCode, [2002, 2003], true) || strpos($wagonMessage, 'Connection refused') !== false) {
            $wagonFatal('اتصال به سرور MySQL برقرار نشد', [
                'مقدار <code>DB_HOST</code> و <code>DB_PORT</code> را بررسی کنید (پیش‌فرض: <code>127.0.0.1:3306</code>).',
                'مطمئن شوید سرویس MySQL/MariaDB روی سرور در حال اجراست.',
                'در بعضی هاست‌ها به‌جای <code>localhost</code> باید از <code>127.0.0.1</code> استفاده شود (یا برعکس).',
                'اگر MySQL روی سوکت یونیکس است، مقدار <code>DB_SOCKET</code> را تنظیم کنید.',
            ]);
        }

        $wagonFatal('خطا در اتصال به دیتابیس MySQL', [
            '<code>'.htmlspecialchars(get_class($wagonDbException), ENT_QUOTES, 'UTF-8').'</code>: '
                .htmlspecialchars($wagonMessage, ENT_QUOTES, 'UTF-8'),
            'مقادیر DB_* را در فایل <code>.env</code> بررسی کنید.',
            'برای تست سریع اتصال، فایل <code>diagnose.php</code> را در مرورگر باز کنید.',
        ]);
    }
} else {
    // حالت جایگزین SQLite (برای تست‌های خودکار و اجرای سبک بدون سرور دیتابیس)
    $wagonDbPath = $wagonBase.'/database/database.sqlite';
    $wagonSqliteName = $wagonEnvValue('DB_DATABASE', null);

    if (is_string($wagonSqliteName) && $wagonSqliteName !== '') {
        $wagonDbPath = (substr($wagonSqliteName, 0, 1) === '/')
            ? $wagonSqliteName
            : $wagonBase.'/'.$wagonSqliteName;
    }

    if (! is_file($wagonDbPath)) {
        @touch($wagonDbPath);
    }

    if (! is_file($wagonDbPath) || ! is_writable($wagonDbPath)) {
        $wagonFatal('فایل دیتابیس SQLite ساخته نشد یا قابل نوشتن نیست', [
            'مسیر مورد انتظار: <code>database/database.sqlite</code>',
            'روی لینوکس یک فایل خالی بسازید: <code>touch database/database.sqlite</code> و دسترسی 664 بدهید.',
            'سپس <code>php artisan migrate --seed</code> را اجرا کنید.',
        ]);
    }
}

try {
    // حالت تعمیر و نگهداری
    if (file_exists($wagonMaintenance = $wagonBase.'/storage/framework/maintenance.php')) {
        require $wagonMaintenance;
    }

    require $wagonAutoload;

    /** @var Application $app */
    $app = require_once $wagonBase.'/bootstrap/app.php';

    $app->handleRequest(Request::capture());
} catch (Throwable $wagonException) {
    $wagonFatal('خطا در راه‌اندازی برنامه', [
        '<code>'.htmlspecialchars(get_class($wagonException), ENT_QUOTES, 'UTF-8').'</code>: '
            .htmlspecialchars($wagonException->getMessage(), ENT_QUOTES, 'UTF-8'),
        'جزئیات کامل در فایل <code>storage/logs/laravel.log</code> ثبت می‌شود.',
        'برای دیدن جزئیات در مرورگر، موقتاً در <code>.env</code> مقدار <code>APP_DEBUG=true</code> بگذارید و صفحه را رفرش کنید '
            .'(بعد از رفع خطا حتماً آن را <code>false</code> کنید).',
        'اگر پیام «No application encryption key» می‌بینید، <code>php artisan key:generate</code> را اجرا کنید.',
        'اگر پیام <code>could not find driver</code> می‌بینید، افزونه <code>pdo_mysql</code> در PHP فعال نیست.',
        'اگر پیام <code>Unknown database</code> یا <code>Access denied</code> می‌بینید، مقادیر DB_* در <code>.env</code> را اصلاح کنید '
            .'یا فایل <code>database/create_database.sql</code> را در phpMyAdmin اجرا کنید.',
    ]);
}
