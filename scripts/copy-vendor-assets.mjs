/**
 * کپی دارایی‌های آفلاین از node_modules به public/vendor
 * اجرا: npm install && npm run assets
 */
import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';

const root = process.cwd();

const files = [
    ['node_modules/bootstrap/dist/css/bootstrap.rtl.min.css', 'public/vendor/bootstrap-rtl/bootstrap.rtl.min.css'],
    ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'public/vendor/bootstrap-rtl/bootstrap.bundle.min.js'],
    ['node_modules/jquery/dist/jquery.min.js', 'public/vendor/jquery/jquery.min.js'],
    ['node_modules/persian-date/dist/persian-date.min.js', 'public/vendor/persian-datepicker/persian-date.min.js'],
    ['node_modules/persian-datepicker/dist/js/persian-datepicker.min.js', 'public/vendor/persian-datepicker/persian-datepicker.min.js'],
    ['node_modules/persian-datepicker/dist/css/persian-datepicker.min.css', 'public/vendor/persian-datepicker/persian-datepicker.min.css'],
    ['node_modules/chart.js/dist/chart.umd.min.js', 'public/vendor/chartjs/chart.umd.min.js'],
];

let copied = 0;

for (const [source, target] of files) {
    const from = join(root, source);
    const to = join(root, target);

    if (!existsSync(from)) {
        console.warn(`[skip] ${source} پیدا نشد. ابتدا npm install را اجرا کنید.`);
        continue;
    }

    mkdirSync(dirname(to), { recursive: true });
    copyFileSync(from, to);
    console.log(`[ok] ${target}`);
    copied += 1;
}

console.log(`\n${copied} فایل کپی شد. فونت وزیرمتن از قبل در public/vendor/fonts موجود است.`);
