/**
 * بررسی وجود دارایی‌های آفلاین لازم برای اجرای برنامه
 * اجرا: npm run assets:check
 */
import { existsSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();

const required = [
    'public/vendor/bootstrap-rtl/bootstrap.rtl.min.css',
    'public/vendor/bootstrap-rtl/bootstrap.bundle.min.js',
    'public/vendor/jquery/jquery.min.js',
    'public/vendor/persian-datepicker/persian-date.min.js',
    'public/vendor/persian-datepicker/persian-datepicker.min.js',
    'public/vendor/persian-datepicker/persian-datepicker.min.css',
    'public/vendor/chartjs/chart.umd.min.js',
    'public/vendor/fonts/vazirmatn.css',
    'public/vendor/fonts/Vazirmatn-Regular.woff2',
    'public/vendor/fonts/Vazirmatn-Bold.woff2',
    'public/css/app.css',
    'storage/fonts/Vazirmatn-Regular.ttf',
    'storage/fonts/Vazirmatn-Bold.ttf',
];

let missing = 0;

for (const file of required) {
    const path = join(root, file);

    if (!existsSync(path) || statSync(path).size === 0) {
        console.error(`[missing] ${file}`);
        missing += 1;
    }
}

if (missing > 0) {
    console.error(`\n${missing} فایل ضروری موجود نیست.`);
    process.exit(1);
}

console.log('همه دارایی‌های آفلاین موجود هستند.');
