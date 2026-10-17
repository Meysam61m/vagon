<?php
/**
 * ابزار عیب‌یابی سامانه مدیریت بارگیری واگن
 * ------------------------------------------
 * این فایل را در مرورگر باز کنید تا وضعیت پیش‌نیازها را ببینید:
 *     https://دامنه-شما/diagnose.php
 *
 * ⚠️ این صفحه اطلاعات فنی سرور را نشان می‌دهد. بعد از رفع خطا آن را حذف کنید.
 * رمزها، APP_KEY و متن ایمیل‌ها نمایش داده نمی‌شوند.
 */

declare(strict_types=1);

$base = __DIR__;

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * خواندن .env با همان قواعد Laravel (کوتیشن، کامنت، export).
 * مقدارهای چندخطی پشتیبانی نمی‌شوند؛ برای رمز دیتابیس هم لازم نیستند.
 */
function read_env_file($path)
{
    if (! is_file($path) || ! is_readable($path)) {
        return [];
    }

    $contents = @file_get_contents($path);

    if (! is_string($contents) || $contents === '') {
        return [];
    }

    $values = [];

    foreach (preg_split('/\r\n|\r|\n/', $contents) as $line) {
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

function mask_log_line($line)
{
    $line = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '***@***', $line);
    $line = preg_replace('/(password|pass|token|secret|key)["\']?\s*[:=]\s*["\']?[^"\'\s,}]+/i', '$1=***', $line);
    $line = preg_replace('/[A-Za-z0-9+\/]{40,}={0,2}/', '***', $line);

    return $line;
}

$checks = [];

function check(&$checks, $title, $state, $value, $hint = '')
{
    $checks[] = [
        'title' => $title,
        'state' => $state, // ok | warn | fail | info
        'value' => $value,
        'hint' => $hint,
    ];
}

$env = read_env_file($base.'/.env');
$envExists = is_file($base.'/.env');

/*
 * -------------------------------------------------------------------------
 * محدودسازی دسترسی به این صفحه
 * -------------------------------------------------------------------------
 * این صفحه اطلاعات فنی سرور (نسخه PHP، مسیرها، کاربر MySQL و ...) را نشان
 * می‌دهد و نباید عمومی باشد. دسترسی فقط در یکی از این دو حالت مجاز است:
 *   ۱) درخواست از خود سرور (127.0.0.1 / ::1) باشد؛
 *   ۲) DIAGNOSE_KEY در فایل .env تنظیم شده و با ?key=... یا هدر
 *      X-Diagnose-Key مطابقت داشته باشد.
 */
$diagnoseKey = isset($env['DIAGNOSE_KEY']) ? trim((string) $env['DIAGNOSE_KEY']) : '';
$remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$isLocalRequest = in_array($remoteAddress, ['127.0.0.1', '::1'], true);

$providedKey = '';

if (isset($_GET['key']) && is_string($_GET['key'])) {
    $providedKey = $_GET['key'];
} elseif (isset($_SERVER['HTTP_X_DIAGNOSE_KEY'])) {
    $providedKey = (string) $_SERVER['HTTP_X_DIAGNOSE_KEY'];
}

$diagnoseAllowed = $isLocalRequest
    || ($diagnoseKey !== '' && hash_equals($diagnoseKey, $providedKey));

if (! $diagnoseAllowed) {
    if (! headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, max-age=0');
    }

    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
        .'<meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<title>دسترسی به ابزار عیب‌یابی بسته است</title><style>'
        .'body{font-family:Tahoma,Arial,sans-serif;background:#f7f7f8;margin:0;padding:32px;color:#111}'
        .'.box{max-width:720px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px 30px}'
        .'h1{font-size:18px;margin:0 0 14px;color:#b45309;line-height:1.8}'
        .'li{margin-bottom:10px;line-height:2}code{background:#f3f4f6;padding:2px 6px;border-radius:4px;direction:ltr;display:inline-block}'
        .'.muted{color:#6b7280;font-size:13px;margin-top:18px;border-top:1px solid #eee;padding-top:12px;line-height:1.9}'
        .'</style></head><body><div class="box">'
        .'<h1>دسترسی به ابزار عیب‌یابی باز نیست</h1><ul>'
        .'<li>این صفحه اطلاعات فنی سرور را نمایش می‌دهد و برای جلوگیری از افشای اطلاعات، فقط از خود سرور یا با کلید دسترسی باز می‌شود.</li>'
        .'<li>یک کلید تصادفی در فایل <code>.env</code> اضافه کنید: <code>DIAGNOSE_KEY=یک-رشته-تصادفی-طولانی</code></li>'
        .'<li>سپس این صفحه را با کلید باز کنید: <code>diagnose.php?key=همان-رشته</code></li>'
        .'<li>یا از داخل سرور با SSH: <code>curl -s http://127.0.0.1/diagnose.php</code></li>'
        .'<li>بعد از رفع خطا، این فایل را کاملاً حذف کنید.</li>'
        .'</ul>'
        .'<div class="muted">اگر فایل <code>.env</code> را تازه تغییر داده‌اید و کلید خوانده نمی‌شود، مطمئن شوید خط <code>DIAGNOSE_KEY</code> بدون فاصله و درست نوشته شده است.</div>'
        .'</div></body></html>';

    exit;
}

if (! headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, max-age=0');
}

// ---------- PHP ----------
$phpOk = PHP_VERSION_ID >= 80200;
check(
    $checks,
    'نسخه PHP',
    $phpOk ? 'ok' : 'fail',
    PHP_VERSION,
    $phpOk ? '' : 'حداقل PHP 8.2 لازم است. در cPanel از «Select PHP Version» نسخه 8.2 یا 8.3 را فعال کنید.'
);

// ---------- افزونه‌های PHP ----------
$extensions = [
    'mbstring' => true,
    'openssl' => true,
    'pdo_mysql' => true,
    'tokenizer' => true,
    'ctype' => true,
    'json' => true,
    'dom' => false,         // برای تولید PDF (بدون آن بقیه برنامه کار می‌کند)
    'fileinfo' => false,
    'session' => true,
    'filter' => true,
    'hash' => true,
    'pdo_sqlite' => false,  // فقط برای تست‌ها و حالت جایگزین
];

$missing = [];

foreach ($extensions as $ext => $required) {
    if (! extension_loaded($ext)) {
        $missing[] = $ext.($required ? '' : ' (اختیاری)');
    }
}

$missingRequired = array_filter($missing, function ($name) {
    return strpos($name, 'اختیاری') === false;
});

check(
    $checks,
    'افزونه‌های PHP',
    $missingRequired ? 'fail' : ($missing ? 'warn' : 'ok'),
    $missing ? 'نصب نیست: '.implode('، ', $missing) : 'همه لازم‌ها موجود است',
    $missingRequired
        ? 'در php.ini یا cPanel → PHP Extensions این افزونه‌ها را فعال کنید: '.implode('، ', $missingRequired)
        : 'بدون افزونه dom، PDF تولید نمی‌شود ولی بقیه صفحات کار می‌کنند.'
);

// ---------- vendor ----------
$vendorExists = is_file($base.'/vendor/autoload.php');
$autoloadOk = false;

if ($vendorExists) {
    try {
        require_once $base.'/vendor/autoload.php';
        $autoloadOk = class_exists('Illuminate\\Foundation\\Application');
    } catch (Throwable $e) {
        $autoloadOk = false;
    }
}

check(
    $checks,
    'پوشه vendor (وابستگی‌های Composer)',
    $vendorExists && $autoloadOk ? 'ok' : 'fail',
    $vendorExists ? ($autoloadOk ? 'موجود و قابل بارگذاری' : 'موجود ولی بارگذاری نشد') : 'وجود ندارد',
    $vendorExists && $autoloadOk ? '' : 'دستور <code>composer install</code> را در ریشه پروژه اجرا کنید. پوشه vendor در فایل zip نیست.'
);

// ---------- .env و APP_KEY ----------
check(
    $checks,
    'فایل .env',
    $envExists ? 'ok' : 'fail',
    $envExists ? 'موجود' : 'وجود ندارد',
    $envExists ? '' : 'فایل <code>.env.example</code> را به <code>.env</code> کپی کنید.'
);

$appKey = isset($env['APP_KEY']) ? $env['APP_KEY'] : '';

check(
    $checks,
    'کلید APP_KEY',
    $appKey !== '' ? 'ok' : 'fail',
    $appKey !== '' ? 'تنظیم شده (مقدار نمایش داده نمی‌شود)' : 'خالی است',
    $appKey !== '' ? '' : 'دستور <code>php artisan key:generate</code> را اجرا کنید.'
);

// ---------- پوشه‌های قابل نوشتن ----------
$dirs = [
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

$dirProblems = [];
$created = [];

foreach ($dirs as $dir) {
    $path = $base.'/'.$dir;

    if (! is_dir($path)) {
        if (@mkdir($path, 0775, true)) {
            $created[] = $dir;
        }
    }

    if (! is_dir($path) || ! is_writable($path)) {
        $dirProblems[] = $dir;
    }
}

check(
    $checks,
    'دسترسی نوشتن پوشه‌ها',
    $dirProblems ? 'fail' : ($created ? 'warn' : 'ok'),
    $dirProblems
        ? 'قابل نوشتن نیست: '.implode('، ', $dirProblems)
        : ($created ? 'ساخته شد: '.implode('، ', $created) : 'همه قابل نوشتن هستند'),
    $dirProblems ? 'روی لینوکس: <code>chmod -R 775 storage bootstrap/cache</code> و مالکیت را به کاربر وب‌سرور بدهید.' : ''
);

// ---------- فایل‌های ظاهری (CSS / JS) ----------
$assetFiles = [
    'public/vendor/bootstrap-rtl/bootstrap.rtl.min.css' => 'استایل بوت‌استرپ RTL',
    'public/vendor/bootstrap-rtl/bootstrap.bundle.min.js' => 'اسکریپت بوت‌استرپ',
    'public/vendor/jquery/jquery.min.js' => 'jQuery',
    'public/vendor/fonts/vazirmatn.css' => 'فونت وزیرمتن',
    'public/vendor/fonts/Vazirmatn-Regular.woff2' => 'فایل فونت وزیرمتن',
    'public/vendor/persian-datepicker/persian-datepicker.min.css' => 'استایل تاریخ‌شمار',
    'public/vendor/persian-datepicker/persian-datepicker.min.js' => 'اسکریپت تاریخ‌شمار',
    'public/vendor/chartjs/chart.umd.min.js' => 'نمودار Chart.js',
    'public/css/app.css' => 'استایل اختصاصی برنامه',
];

$missingAssets = [];

foreach ($assetFiles as $assetPath => $assetTitle) {
    if (! is_file($base.'/'.$assetPath)) {
        $missingAssets[] = $assetTitle.' (<code>'.$assetPath.'</code>)';
    }
}

check(
    $checks,
    'فایل‌های ظاهری (CSS/JS)',
    $missingAssets ? 'fail' : 'ok',
    $missingAssets ? 'کم است: '.count($missingAssets).' فایل' : 'همه فایل‌های ظاهری موجودند',
    $missingAssets
        ? 'این فایل‌ها آپلود نشده‌اند؛ در نتیجه صفحه بدون استایل (شبیه یک لیست ساده) دیده می‌شود. '
            .'پوشه <code>public/vendor</code> و <code>public/css/app.css</code> را از بسته کامل دوباره آپلود کنید.<br>'
            .'موارد کم: '.implode('، ', array_slice($missingAssets, 0, 5))
        : ''
);

// ---------- دیتابیس ----------
$dbDriver = isset($env['DB_CONNECTION']) && $env['DB_CONNECTION'] !== '' ? strtolower($env['DB_CONNECTION']) : 'mysql';
$dbName = isset($env['DB_DATABASE']) && $env['DB_DATABASE'] !== '' ? $env['DB_DATABASE'] : 'wagon_system';

if (in_array($dbDriver, ['mysql', 'mariadb'], true)) {
    $dbHost = isset($env['DB_HOST']) && $env['DB_HOST'] !== '' ? $env['DB_HOST'] : '127.0.0.1';
    $dbPort = isset($env['DB_PORT']) && $env['DB_PORT'] !== '' ? $env['DB_PORT'] : '3306';
    $dbUser = isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : 'root';
    $dbPass = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
    $dbSocket = isset($env['DB_SOCKET']) ? $env['DB_SOCKET'] : '';

    $targets = [];

    if ($dbSocket !== '') {
        $targets[] = [
            'label' => 'socket '.$dbSocket,
            'dsn' => 'mysql:unix_socket='.$dbSocket.';dbname='.$dbName.';charset=utf8mb4',
        ];
    } else {
        $targets[] = [
            'label' => $dbHost.':'.$dbPort,
            'dsn' => 'mysql:host='.$dbHost.';port='.$dbPort.';dbname='.$dbName.';charset=utf8mb4',
        ];

        if (in_array(strtolower($dbHost), ['127.0.0.1', 'localhost'], true)) {
            $altHost = strtolower($dbHost) === 'localhost' ? '127.0.0.1' : 'localhost';
            $targets[] = [
                'label' => $altHost.':'.$dbPort,
                'dsn' => 'mysql:host='.$altHost.';port='.$dbPort.';dbname='.$dbName.';charset=utf8mb4',
            ];
        }
    }

    $pdo = null;
    $connectError = '';
    $errorCode = 0;
    $workingLabel = '';
    $triedLabels = [];
    $lastLabel = $targets[0]['label'];

    if (! extension_loaded('pdo_mysql')) {
        $connectError = 'افزونه pdo_mysql بارگذاری نشده است.';
    } else {
        foreach ($targets as $target) {
            $triedLabels[] = $target['label'];
            $lastLabel = $target['label'];

            try {
                $pdo = new PDO($target['dsn'], $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3,
                ]);
                $workingLabel = $target['label'];

                break;
            } catch (Throwable $e) {
                $pdo = null;
                $connectError = $e->getMessage();
                $errorCode = (int) ($e instanceof PDOException ? ($e->errorInfo[1] ?? 0) : 0);
            }
        }
    }

    $triedText = implode(' و ', $triedLabels !== [] ? $triedLabels : [$lastLabel]);
    $passInfo = $dbPass === '' ? 'خالی' : strlen($dbPass).' کاراکتر';

    check(
        $checks,
        'مشخصات اتصال MySQL',
        'info',
        'کاربر '.$dbUser.' — مسیر '.$triedText.' — دیتابیس '.$dbName.' — رمز: '.$passInfo,
        'رمز نمایش داده نمی‌شود. اگر مقدار رمز در .env داخل کوتیشن نباشد و شامل # باشد، بخش بعد از # کامنت می‌شود؛ آن را به شکل <code>DB_PASSWORD="..."</code> بنویسید.'
    );

    check(
        $checks,
        'اتصال به MySQL',
        $pdo ? 'ok' : 'fail',
        $pdo
            ? 'اتصال برقرار شد ('.$workingLabel.' — دیتابیس '.$dbName.')'
            : 'اتصال برقرار نشد',
        $pdo ? '' : match (true) {
            $errorCode === 1049 => 'دیتابیس <code>'.$dbName.'</code> وجود ندارد. فایل <code>database/create_database.sql</code> را در phpMyAdmin اجرا کنید.',
            $errorCode === 1044 => 'کاربر <code>'.$dbUser.'</code> به دیتابیس <code>'.$dbName.'</code> دسترسی ندارد. در cPanel کاربر را با Add User To Database و ALL PRIVILEGES به دیتابیس اضافه کنید.',
            $errorCode === 1045 => 'نام کاربری یا رمز پذیرفته نشد (Access denied). '
                .'کاربر <code>'.$dbUser.'</code> را با مقدار کامل cPanel (مثل <code>user_wagon</code>) مقایسه کنید، رمز را داخل کوتیشن بگذارید و مطمئن شوید کاربر به دیتابیس اضافه شده است. '.
                'هر دو مسیر localhost و 127.0.0.1 امتحان شد.',
            $errorCode === 2002, $errorCode === 2003 => 'سرور MySQL در دسترس نیست؛ DB_HOST/DB_PORT و اجرا بودن سرویس را بررسی کنید.',
            strpos($connectError, 'pdo_mysql') !== false => 'افزونه <code>pdo_mysql</code> را در php.ini یا cPanel فعال کنید.',
            default => 'جزئیات (از '.$lastLabel.'): '.h($connectError),
        }
    );

    if ($pdo && count($triedLabels) > 1 && $workingLabel !== $triedLabels[0]) {
        check(
            $checks,
            'آدرس اتصال جایگزین',
            'warn',
            'اتصال از '.$workingLabel.' برقرار شد، نه از '.$triedLabels[0],
            'مقدار <code>DB_HOST</code> را در <code>.env</code> به <code>'.h($workingLabel).'</code> تغییر دهید و سپس <code>php artisan config:clear</code> را اجرا کنید.'
        );
    }

    if (is_file($base.'/bootstrap/cache/config.php')) {
        check(
            $checks,
            'کش تنظیمات (config cache)',
            'warn',
            'فایل bootstrap/cache/config.php وجود دارد',
            'لاراول به‌جای .env از این فایل کش‌شده می‌خواند؛ اگر DB_* را عوض کردید و اثر نکرد، <code>php artisan config:clear</code> را اجرا کنید (یا فایل را حذف کنید).'
        );
    }


    if ($pdo) {
        $serverVersion = '';

        try {
            $serverVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (Throwable $e) {
            $serverVersion = 'نامشخص';
        }

        check($checks, 'نسخه سرور MySQL', 'info', $serverVersion);

        $tableCount = null;
        $userCount = null;
        $dbError = '';

        try {
            $tableCount = (int) $pdo->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn();

            $hasUsers = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'"
            )->fetchColumn();

            if ($hasUsers > 0) {
                $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            }
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        check(
            $checks,
            'جداول دیتابیس',
            $tableCount === null ? 'fail' : ($tableCount > 5 ? 'ok' : 'fail'),
            $tableCount === null ? 'خطا: '.$dbError : $tableCount.' جدول در دیتابیس '.$dbName,
            ($tableCount !== null && $tableCount > 5) ? '' : 'دستور <code>php artisan migrate --seed</code> را اجرا کنید.'
        );

        check(
            $checks,
            'کاربران سیستم',
            $userCount === null ? 'warn' : ($userCount > 0 ? 'ok' : 'fail'),
            $userCount === null ? 'جدول users وجود ندارد' : $userCount.' کاربر',
            ($userCount !== null && $userCount > 0) ? '' : 'seeder اجرا نشده است؛ <code>php artisan db:seed --force</code> یا <code>php artisan admin:reset-password</code>.'
        );
    }
} else {
    $dbPath = $base.'/database/database.sqlite';

    if (isset($env['DB_DATABASE']) && $env['DB_DATABASE'] !== '') {
        $dbPath = (substr($env['DB_DATABASE'], 0, 1) === '/')
            ? $env['DB_DATABASE']
            : $base.'/'.$env['DB_DATABASE'];
    }

    $dbExists = is_file($dbPath);

    check(
        $checks,
        'فایل دیتابیس SQLite (حالت جایگزین)',
        $dbExists ? 'ok' : 'fail',
        $dbExists ? 'موجود در database/database.sqlite' : 'وجود ندارد',
        $dbExists ? '' : 'فایل خالی بسازید و <code>php artisan migrate --seed</code> را اجرا کنید.'
    );

    if ($dbExists) {
        $tableCount = null;
        $userCount = null;
        $dbError = '';

        try {
            $pdo = new PDO('sqlite:'.$dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            $tableCount = count($tables);

            if (in_array('users', $tables, true)) {
                $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            }
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }

        check(
            $checks,
            'جداول دیتابیس',
            $tableCount === null ? 'fail' : ($tableCount > 5 ? 'ok' : 'fail'),
            $tableCount === null ? 'خطا: '.$dbError : $tableCount.' جدول',
            ($tableCount !== null && $tableCount > 5) ? '' : 'دستور <code>php artisan migrate --seed</code> را اجرا کنید.'
        );

        check(
            $checks,
            'کاربران سیستم',
            $userCount === null ? 'warn' : ($userCount > 0 ? 'ok' : 'fail'),
            $userCount === null ? 'قابل خواندن نیست' : $userCount.' کاربر',
            ($userCount !== null && $userCount > 0) ? '' : 'seeder اجرا نشده است؛ <code>php artisan db:seed --force</code>.'
        );
    }
}

// ---------- دارایی‌های آفلاین ----------
$assets = [
    'public/vendor/bootstrap-rtl/bootstrap.rtl.min.css',
    'public/vendor/bootstrap-rtl/bootstrap.bundle.min.js',
    'public/vendor/jquery/jquery.min.js',
    'public/vendor/persian-datepicker/persian-date.min.js',
    'public/vendor/persian-datepicker/persian-datepicker.min.js',
    'public/vendor/fonts/vazirmatn.css',
    'public/vendor/chartjs/chart.umd.min.js',
    'public/css/app.css',
    'storage/fonts/Vazirmatn-Regular.ttf',
];

$missingAssets = [];

foreach ($assets as $asset) {
    if (! is_file($base.'/'.$asset)) {
        $missingAssets[] = $asset;
    }
}

check(
    $checks,
    'فایل‌های استاتیک (آفلاین)',
    $missingAssets ? 'fail' : 'ok',
    $missingAssets ? 'کم است: '.implode('، ', $missingAssets) : 'همه موجود است',
    $missingAssets ? 'پوشه public/vendor را دوباره از فایل zip استخراج کنید.' : ''
);

// ---------- ریسه سایت / htaccess ----------
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$publicPath = realpath($base.'/public');

$rootState = 'info';
$rootValue = 'DOCUMENT_ROOT: '.(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'نامشخص');
$rootHint = '';

if ($docRoot !== false && $publicPath !== false) {
    if ($docRoot === $publicPath) {
        $rootState = 'ok';
        $rootValue = 'ریشه سایت روی public تنظیم شده است (درست)';
    } elseif ($docRoot === $base) {
        $rootState = 'warn';
        $rootValue = 'ریشه سایت روی پوشه پروژه است، نه public';
        $rootHint = 'فایل <code>.htaccess</code> ریشه باید فعال باشد (mod_rewrite). بهتر است document root را روی <code>public</code> بگذارید.';
    } else {
        $rootValue .= ' — public: '.$publicPath;
    }
}

check($checks, 'ریشه سایت (Document Root)', $rootState, $rootValue, $rootHint);

$rewriteState = 'info';
$rewriteValue = 'mod_rewrite قابل تشخیص نیست (Apache نیست یا تابع امنیتی غیرفعال است)';

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $rewriteValue = in_array('mod_rewrite', $modules, true) ? 'فعال' : 'غیرفعال';
    $rewriteState = in_array('mod_rewrite', $modules, true) ? 'ok' : 'fail';
}

check($checks, 'ماژول mod_rewrite', $rewriteState, $rewriteValue, $rewriteState === 'fail' ? 'در cPanel یا httpd.conf ماژول rewrite_module را فعال کنید.' : '');

// ---------- لاگ ----------
$logPath = $base.'/storage/logs/laravel.log';
$logExists = is_file($logPath);
$logSize = $logExists ? filesize($logPath) : 0;

check(
    $checks,
    'فایل لاگ',
    $logExists ? 'ok' : 'warn',
    $logExists ? 'موجود ('.number_format($logSize / 1024, 1).' کیلوبایت)' : 'وجود ندارد (هنوز خطایی ثبت نشده)',
    $logExists ? 'برای دیدن ۳۰ خط آخر، به انتهای همین صفحه بروید.' : 'اگر صفحه ۵۰۰ می‌دهد ولی لاگ خالی/غایب است، خطا قبل از بوت لاراول رخ داده (vendor، APP_KEY، دسترسی پوشه‌ها).'
);

$showLog = isset($_GET['log']) && $_GET['log'] === '1';
$logLines = [];

if ($showLog && $logExists && is_readable($logPath)) {
    $content = @file($logPath, FILE_IGNORE_NEW_LINES) ?: [];
    $logLines = array_slice($content, -30);
}

$fails = 0;
$warns = 0;

foreach ($checks as $check) {
    if ($check['state'] === 'fail') {
        $fails++;
    } elseif ($check['state'] === 'warn') {
        $warns++;
    }
}

$summaryState = $fails > 0 ? 'fail' : ($warns > 0 ? 'warn' : 'ok');
$summaryText = $fails > 0
    ? $fails.' مشکل جدی پیدا شد (موارد قرمز)'
    : ($warns > 0 ? $warns.' هشدار؛ برنامه احتمالاً اجرا می‌شود' : 'همه پیش‌نیازها درست است');

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عیب‌یابی سامانه بارگیری واگن</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f6f7f9; margin: 0; padding: 28px; color: #111; }
        .wrap { max-width: 980px; margin: 0 auto; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .sub { color: #6b7280; font-size: 13px; margin-bottom: 18px; line-height: 1.9; }
        .warn-box { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 18px; line-height: 1.9; }
        .summary { padding: 14px 18px; border-radius: 10px; font-weight: bold; margin-bottom: 18px; }
        .summary.ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
        .summary.warn { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
        .summary.fail { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        th, td { padding: 11px 13px; border-bottom: 1px solid #eef0f3; text-align: right; vertical-align: top; font-size: 13px; line-height: 1.8; }
        th { background: #f9fafb; font-size: 12px; color: #4b5563; }
        tr:last-child td { border-bottom: 0; }
        .tag { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12px; white-space: nowrap; }
        .tag.ok { background: #dcfce7; color: #166534; }
        .tag.warn { background: #fef3c7; color: #92400e; }
        .tag.fail { background: #fee2e2; color: #991b1b; }
        .tag.info { background: #e0f2fe; color: #075985; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; direction: ltr; display: inline-block; font-family: Consolas, monospace; font-size: 12px; }
        .hint { color: #6b7280; font-size: 12px; margin-top: 4px; }
        .log { background: #0f172a; color: #e2e8f0; padding: 16px; border-radius: 10px; direction: ltr; text-align: left; overflow: auto; font-family: Consolas, monospace; font-size: 11.5px; line-height: 1.7; white-space: pre-wrap; margin-top: 18px; }
        .actions { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 9px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; border: 1px solid #d1d5db; color: #111; background: #fff; }
        .btn.primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .foot { color: #9ca3af; font-size: 12px; margin-top: 22px; line-height: 1.9; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>عیب‌یابی سامانه مدیریت بارگیری واگن</h1>
    <div class="sub">
        این صفحه پیش‌نیازهای اجرای برنامه را بررسی می‌کند. موارد قرمز باید اول برطرف شوند.
        <br>مسیر پروژه: <code><?= h($base) ?></code>
    </div>

    <div class="warn-box">
        ⚠️ این فایل اطلاعات فنی سرور را نشان می‌دهد. پس از رفع مشکل، فایل <code>diagnose.php</code> را از سرور حذف کنید.
    </div>

    <div class="summary <?= h($summaryState) ?>"><?= h($summaryText) ?></div>

    <table>
        <thead>
        <tr>
            <th style="width: 70px;">وضعیت</th>
            <th style="width: 220px;">بررسی</th>
            <th>نتیجه</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($checks as $check): ?>
            <tr>
                <td><span class="tag <?= h($check['state']) ?>"><?= $check['state'] === 'ok' ? 'درست' : ($check['state'] === 'warn' ? 'هشدار' : ($check['state'] === 'fail' ? 'مشکل' : 'اطلاع')) ?></span></td>
                <td><?= h($check['title']) ?></td>
                <td>
                    <?= h($check['value']) ?>
                    <?php if ($check['hint'] !== ''): ?>
                        <div class="hint"><?= $check['hint'] ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="actions">
        <a class="btn primary" href="<?= h($_SERVER['SCRIPT_NAME'] ?? 'diagnose.php') ?>?log=1">نمایش ۳۰ خط آخر لاگ</a>
        <a class="btn" href="<?= h(dirname($_SERVER['SCRIPT_NAME'] ?? '/') === '/' ? '/' : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/').'/') ?>">رفتن به برنامه</a>
    </div>

    <?php if ($showLog): ?>
        <h3 style="margin-top:24px;font-size:15px;">۳۰ خط آخر <code>storage/logs/laravel.log</code></h3>
        <?php if (! $logLines): ?>
            <p class="hint">لاگ خالی است یا خواندنی نیست. اگر صفحه ۵۰۰ می‌دهد ولی لاگ خالی است، خطا قبل از بوت لاراول رخ داده است (موارد بالای همین جدول).</p>
        <?php else: ?>
            <div class="log"><?= h(implode("\n", array_map('mask_log_line', $logLines))) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="foot">
        <strong>راهنمای سریع رفع خطای ۵۰۰:</strong>
        <ol>
            <li>پوشه <code>vendor</code> وجود ندارد → <code>composer install</code></li>
            <li><code>APP_KEY</code> خالی است → <code>php artisan key:generate</code></li>
            <li>پوشه‌های <code>storage</code> و <code>bootstrap/cache</code> قابل نوشتن نیستند → <code>chmod -R 775 storage bootstrap/cache</code></li>
            <li>خطای MySQL (<code>Unknown database</code> / <code>Access denied</code>) → دیتابیس را بسازید و DB_* را در <code>.env</code> اصلاح کنید</li>
            <li>جداول ساخته نشده‌اند → <code>php artisan migrate --seed</code></li>
            <li>افزونه <code>pdo_mysql</code> فعال نیست → در php.ini یا cPanel آن را فعال کنید</li>
            <li>نسخه PHP کمتر از 8.2 است → نسخه PHP را ارتقا دهید</li>
            <li>ریشه سایت <code>public</code> نیست → document root را روی <code>public</code> بگذارید یا <code>.htaccess</code> ریشه را فعال نگه دارید</li>
        </ol>
    </div>
</div>
</body>
</html>
