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

/**
 * خواندن ساده و امن فایل .env (بدون نیاز به Composer).
 *
 * قواعد مهم:
 *  - مقدارهای داخل "..." و '...' به‌صورت کامل خوانده می‌شوند؛
 *  - # فقط وقتی کامنت است که قبلش فاصله باشد (مثل رفتار Laravel)؛
 *  - پیشوند export و فاصله‌های دور = هم پشتیبانی می‌شوند.
 */
if (! function_exists('wagonEnvParse')) {
    function wagonEnvParse($contents)
    {
        $values = [];

        if (! is_string($contents) || $contents === '') {
            return $values;
        }

        $lines = preg_split('/\r\n|\r|\n/', $contents);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            if (stripos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }

            $position = strpos($line, '=');

            if ($position === false) {
                continue;
            }

            $key = trim(substr($line, 0, $position));

            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $key)) {
                continue;
            }

            $raw = ltrim(substr($line, $position + 1));

            if ($raw !== '' && ($raw[0] === '"' || $raw[0] === "'")) {
                $quote = $raw[0];
                $length = strlen($raw);
                $value = '';

                for ($i = 1; $i < $length; $i++) {
                    $character = $raw[$i];

                    if ($character === '\\' && $quote === '"' && $i + 1 < $length) {
                        $next = $raw[++$i];
                        $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", '"' => '"', '\\' => '\\'];
                        $value .= isset($escapes[$next]) ? $escapes[$next] : '\\'.$next;
                        continue;
                    }

                    if ($character === $quote) {
                        break;
                    }

                    $value .= $character;
                }
            } else {
                $raw = preg_replace('/\s+#.*$/', '', $raw);
                $value = trim($raw);
            }

            $values[$key] = $value;
        }

        return $values;
    }
}

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
$wagonEnvValues = wagonEnvParse($wagonEnv);
$wagonAppKey = getenv('APP_KEY');

if ($wagonAppKey === false && isset($wagonEnvValues['APP_KEY']) && $wagonEnvValues['APP_KEY'] !== '') {
    $wagonAppKey = $wagonEnvValues['APP_KEY'];
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
$wagonEnvValue = function (string $key, $default = null) use ($wagonEnvValues) {
    if (array_key_exists($key, $wagonEnvValues) && $wagonEnvValues[$key] !== '') {
        return $wagonEnvValues[$key];
    }

    $env = getenv($key);

    return ($env === false || $env === '') ? $default : $env;
};

$wagonDbDriver = strtolower((string) $wagonEnvValue('DB_CONNECTION', 'mysql'));
$wagonDbName = (string) $wagonEnvValue('DB_DATABASE', 'wagon_system');

/*
 * در حالت production جزئیات اتصال (نام کاربری، مسیر، متن خطای MySQL)
 * نمایش داده نمی‌شود تا اطلاعات سرور به بازدیدکننده عمومی نشت نکند.
 * برای دیدن این جزئیات، موقتاً APP_DEBUG=true بگذارید.
 */
$wagonDebug = filter_var($wagonEnvValue('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)
    || strtolower((string) $wagonEnvValue('APP_ENV', 'production')) === 'local';

$wagonDbVerifiedMarker = $wagonBase.'/storage/framework/.wagon_db_verified_v1';

if (in_array($wagonDbDriver, ['mysql', 'mariadb'], true) && ! is_file($wagonDbVerifiedMarker)) {
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

    /*
     * روی بعضی هاست‌ها اتصال با localhost (سوکت) جواب می‌دهد و روی بعضی با
     * 127.0.0.1 (TCP)؛ چون کاربر MySQL ممکن است فقط برای یکی از این دو تعریف
     * شده باشد، هر دو حالت امتحان می‌شود تا خطای «Access denied» بی‌دلیل
     * نمایش داده نشود.
     */
    $wagonDbTargets = [];

    if ($wagonDbSocket !== '') {
        $wagonDbTargets[] = [
            'label' => 'socket '.$wagonDbSocket,
            'dsn' => 'mysql:unix_socket='.$wagonDbSocket.';dbname='.$wagonDbName.';charset=utf8mb4',
        ];
    } else {
        $wagonDbTargets[] = [
            'label' => $wagonDbHost.':'.$wagonDbPort,
            'dsn' => 'mysql:host='.$wagonDbHost.';port='.$wagonDbPort.';dbname='.$wagonDbName.';charset=utf8mb4',
        ];

        if (in_array(strtolower($wagonDbHost), ['127.0.0.1', 'localhost'], true)) {
            $wagonAltHost = strtolower($wagonDbHost) === 'localhost' ? '127.0.0.1' : 'localhost';

            $wagonDbTargets[] = [
                'label' => $wagonAltHost.':'.$wagonDbPort,
                'dsn' => 'mysql:host='.$wagonAltHost.';port='.$wagonDbPort.';dbname='.$wagonDbName.';charset=utf8mb4',
            ];
        }
    }

    $wagonPdo = null;
    $wagonLastCode = 0;
    $wagonLastMessage = '';
    $wagonLastLabel = $wagonDbTargets[0]['label'];
    $wagonLastClass = 'PDOException';
    $wagonWorkingLabel = '';
    $wagonTriedLabels = [];

    foreach ($wagonDbTargets as $wagonTarget) {
        $wagonTriedLabels[] = $wagonTarget['label'];

        try {
            $wagonPdo = new PDO($wagonTarget['dsn'], $wagonDbUser, $wagonDbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $wagonWorkingLabel = $wagonTarget['label'];

            break;
        } catch (PDOException $wagonDbException) {
            $wagonPdo = null;
            $wagonLastCode = (int) ($wagonDbException->errorInfo[1] ?? 0);
            $wagonLastMessage = $wagonDbException->getMessage();
            $wagonLastLabel = $wagonTarget['label'];
            $wagonLastClass = get_class($wagonDbException);
        }
    }

    $wagonTriedText = htmlspecialchars(implode(' و ', $wagonTriedLabels), ENT_QUOTES, 'UTF-8');
    $wagonSafeUser = htmlspecialchars($wagonDbUser, ENT_QUOTES, 'UTF-8');
    $wagonSafeName = htmlspecialchars($wagonDbName, ENT_QUOTES, 'UTF-8');
    $wagonPassHint = $wagonDbPass === ''
        ? 'رمز خوانده‌شده از <code>.env</code> <strong>خالی</strong> است.'
        : 'رمزی که از <code>.env</code> خوانده شد '.strlen($wagonDbPass).' کاراکتر است (محتوای رمز نمایش داده نمی‌شود).';

    if (! $wagonPdo) {
        $wagonCode = $wagonLastCode;
        $wagonMessage = $wagonLastMessage;

        if ($wagonCode === 1049 || strpos($wagonMessage, 'Unknown database') !== false) {
            $wagonFatal($wagonDebug ? 'دیتابیس «'.$wagonDbName.'» وجود ندارد' : 'دیتابیس برنامه وجود ندارد', [
                $wagonDebug
                    ? 'اتصال به MySQL برقرار شد ولی دیتابیس <code>'.$wagonSafeName.'</code> ساخته نشده است.'
                    : 'اتصال به MySQL برقرار شد ولی دیتابیس برنامه هنوز ساخته نشده است.',
                'در phpMyAdmin یا MySQL دیتابیس برنامه را با انکودینگ <code>utf8mb4_unicode_ci</code> ایجاد کنید.',
                'یا فایل <code>database/create_database.sql</code> را در phpMyAdmin اجرا کنید.',
                'سپس <code>php artisan migrate --seed</code> را اجرا کنید.',
            ]);
        }

        if ($wagonCode === 1044) {
            $wagonFatal($wagonDebug ? 'کاربر MySQL به دیتابیس «'.$wagonDbName.'» دسترسی ندارد' : 'کاربر MySQL به دیتابیس برنامه دسترسی ندارد', [
                $wagonDebug
                    ? 'کاربر <code>'.$wagonSafeUser.'</code> به MySQL وصل شد، اما اجازه استفاده از دیتابیس <code>'.$wagonSafeName.'</code> را ندارد.'
                    : 'کاربر MySQL به دیتابیس برنامه دسترسی ندارد.',
                'در cPanel → <strong>MySQL Databases</strong> → بخش <strong>Add User To Database</strong> کاربر را به این دیتابیس اضافه کنید.',
                'هنگام اضافه‌کردن، گزینه <strong>ALL PRIVILEGES</strong> را انتخاب کنید.',
                $wagonDebug
                    ? 'در هاست‌های دیگر: <code>GRANT ALL PRIVILEGES ON `'.$wagonSafeName.'`.* TO \''.$wagonSafeUser.'\'@\'localhost\';</code>'
                    : 'در هاست‌های دیگر، از پنل مدیریت دیتابیس دسترسی کامل (ALL PRIVILEGES) به این دیتابیس بدهید.',
                'سپس صفحه را رفرش کنید.',
            ]);
        }

        if ($wagonCode === 1045 || strpos($wagonMessage, 'Access denied') !== false) {
            $wagonHints = [];

            if ($wagonDebug) {
                $wagonHints[] = 'مشخصات اتصال: کاربر <code>'.$wagonSafeUser.'</code>، مسیر <code>'.$wagonTriedText.'</code>، دیتابیس <code>'.$wagonSafeName.'</code>.';
                $wagonHints[] = $wagonPassHint;
            }

            $wagonHints[] = 'در cPanel نام کاربری دیتابیس معمولاً پیشوند حساب هاست را دارد؛ مثلاً <code>myuser_wagon</code> نه فقط <code>wagon</code>. مقدار کامل را در <code>DB_USERNAME</code> بگذارید.';
            $wagonHints[] = 'در cPanel → <strong>MySQL Databases</strong> مطمئن شوید کاربر ساخته شده و با <strong>Add User To Database</strong> به دیتابیس اضافه شده است.';
            $wagonHints[] = 'اگر رمز شامل <code>#</code>، فاصله یا کاراکترهای خاص است، آن را داخل دابل‌کوتیشن بگذارید: <code>DB_PASSWORD="p@ss#123"</code> (بدون کوتیشن، بخش بعد از # کامنت حساب می‌شود).';
            $wagonHints[] = 'روی XAMPP / Laragon رمز پیش‌فرض کاربر <code>root</code> معمولاً خالی است؛ اگر برای root رمز گذاشته‌اید همان را در <code>DB_PASSWORD</code> بگذارید.';
            $wagonHints[] = 'در هاست اشتراکی کاربر <code>root</code> معمولاً غیرفعال است؛ باید کاربر دیتابیس مخصوص خودتان (با پیشوند حساب) را بسازید و در <code>DB_USERNAME</code> بگذارید.';

            if ($wagonDebug) {
                $wagonHints[] = 'هر دو حالت <code>localhost</code> و <code>127.0.0.1</code> امتحان شد؛ رد شدن هر دو یعنی کاربر/رمز روی سرور پذیرفته نمی‌شود، نه اینکه آدرس اشتباه باشد.';
            }

            if (is_file($wagonBase.'/bootstrap/cache/config.php')) {
                $wagonHints[] = 'فایل <code>bootstrap/cache/config.php</code> وجود دارد و مقادیر قدیمی کش شده‌اند؛ یک بار <code>php artisan config:clear</code> را اجرا کنید (یا این فایل را حذف کنید).';
            }

            $wagonFatal('نام کاربری یا رمز MySQL درست نیست (Access denied)', $wagonHints);
        }

        if (in_array($wagonCode, [2002, 2003], true) || strpos($wagonMessage, 'Connection refused') !== false) {
            $wagonHints = [];

            if ($wagonDebug) {
                $wagonHints[] = 'مسیرهای امتحان‌شده: <code>'.$wagonTriedText.'</code>.';
            }

            $wagonHints[] = 'مقدار <code>DB_HOST</code> و <code>DB_PORT</code> را بررسی کنید (پیش‌فرض: <code>127.0.0.1:3306</code>).';

            $wagonFatal('اتصال به سرور MySQL برقرار نشد', array_merge($wagonHints, [
                'مطمئن شوید سرویس MySQL/MariaDB روی سرور در حال اجراست.',
                'در بعضی هاست‌ها به‌جای <code>localhost</code> باید از <code>127.0.0.1</code> استفاده شود (یا برعکس).',
                'اگر MySQL روی سوکت یونیکس است، مقدار <code>DB_SOCKET</code> را تنظیم کنید.',
            ]));
        }

        $wagonHints = [];

        if ($wagonDebug) {
            $wagonHints[] = 'مسیرهای امتحان‌شده: <code>'.$wagonTriedText.'</code> — آخرین خطا از <code>'.$wagonLastLabel.'</code>:';
            $wagonHints[] = '<code>'.$wagonLastClass.'</code>: '.htmlspecialchars($wagonMessage, ENT_QUOTES, 'UTF-8');
        } else {
            $wagonHints[] = 'جزئیات فنی خطا برای امنیت نمایش داده نمی‌شود؛ برای دیدن آن موقتاً در <code>.env</code> مقدار <code>APP_DEBUG=true</code> بگذارید.';
        }

        $wagonHints[] = 'مقادیر DB_* را در فایل <code>.env</code> بررسی کنید.';

        $wagonFatal('خطا در اتصال به دیتابیس MySQL', $wagonHints);
    }

    try {
        $wagonTableCount = (int) $wagonPdo->query(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchColumn();
    } catch (PDOException $wagonSchemaException) {
        $wagonHints = [
            $wagonDebug
                ? 'کاربر <code>'.$wagonSafeUser.'</code> به دیتابیس <code>'.$wagonSafeName.'</code> وصل شد ولی دسترسی خواندن <code>information_schema</code> را ندارد.'
                : 'کاربر MySQL به دیتابیس برنامه وصل شد ولی اجازه خواندن اطلاعات آن را ندارد.',
            'در cPanel → MySQL Databases → Add User To Database گزینه <strong>ALL PRIVILEGES</strong> را بدهید.',
        ];

        if ($wagonDebug) {
            $wagonHints[] = 'جزئیات خطا: <code>'.htmlspecialchars($wagonSchemaException->getMessage(), ENT_QUOTES, 'UTF-8').'</code>';
        }

        $wagonFatal('اتصال به MySQL برقرار شد، اما خواندن اطلاعات دیتابیس ممکن نشد', $wagonHints);
    }

    if ($wagonTableCount === 0) {
        $wagonFatal('اتصال به MySQL برقرار شد، اما دیتابیس خالی است', [
            $wagonDebug
                ? 'دیتابیس <code>'.$wagonSafeName.'</code> ساخته شده اما هیچ جدولی ندارد.'
                : 'دیتابیس برنامه ساخته شده اما هیچ جدولی ندارد.',
            'دستور <code>php artisan migrate --seed</code> را در پوشه پروژه اجرا کنید.',
            'اگر از cPanel اجرا می‌کنید، در بخش «Terminal» یا از طریق SSH این دستور را بزنید.',
        ]);
    }

    @file_put_contents($wagonDbVerifiedMarker, '1');
    $wagonPdo = null;
} elseif (! in_array($wagonDbDriver, ['mysql', 'mariadb'], true)) {
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

    // اگر فایل کش مسیرها (routes-v7.php) از قبل مانده باشد و مسیرهای جدید را نداشته باشد،
    // به‌صورت خودکار پاک می‌شود تا خطای Route not defined رخ ندهد.
    $wagonRouteCache = $wagonBase.'/bootstrap/cache/routes-v7.php';
    $wagonWebRoutes = $wagonBase.'/routes/web.php';
    if (is_file($wagonRouteCache)) {
        $wagonCacheContent = @file_get_contents($wagonRouteCache);
        $wagonStaleByTime = is_file($wagonWebRoutes) && @filemtime($wagonRouteCache) < @filemtime($wagonWebRoutes);
        $wagonMissingOwnerRoute = $wagonCacheContent === false || ! str_contains($wagonCacheContent, 'wagons.edit-owner');

        if ($wagonStaleByTime || $wagonMissingOwnerRoute) {
            @unlink($wagonRouteCache);
            clearstatcache(true, $wagonRouteCache);
        }
    }

    /** @var Application $app */
    $app = require_once $wagonBase.'/bootstrap/app.php';

    $app->handleRequest(Request::capture());
} catch (Throwable $wagonException) {
    $wagonErrorHints = [];

    if ($wagonDebug) {
        $wagonErrorHints[] = '<code>'.htmlspecialchars(get_class($wagonException), ENT_QUOTES, 'UTF-8').'</code>: '
            .htmlspecialchars($wagonException->getMessage(), ENT_QUOTES, 'UTF-8');
    } else {
        $wagonErrorHints[] = 'جزئیات فنی خطا برای امنیت نمایش داده نمی‌شود؛ برای دیدن آن موقتاً <code>APP_DEBUG=true</code> بگذارید.';
    }

    $wagonFatal('خطا در راه‌اندازی برنامه', array_merge($wagonErrorHints, [
        'جزئیات کامل در فایل <code>storage/logs/laravel.log</code> ثبت می‌شود.',
        'برای دیدن جزئیات در مرورگر، موقتاً در <code>.env</code> مقدار <code>APP_DEBUG=true</code> بگذارید و صفحه را رفرش کنید '
            .'(بعد از رفع خطا حتماً آن را <code>false</code> کنید).',
        'اگر پیام «No application encryption key» می‌بینید، <code>php artisan key:generate</code> را اجرا کنید.',
        'اگر پیام <code>could not find driver</code> می‌بینید، افزونه <code>pdo_mysql</code> در PHP فعال نیست.',
        'اگر پیام <code>Unknown database</code> یا <code>Access denied</code> می‌بینید، مقادیر DB_* در <code>.env</code> را اصلاح کنید '
            .'یا فایل <code>database/create_database.sql</code> را در phpMyAdmin اجرا کنید.',
    ]));
}
