<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password
                            {email? : ایمیل کاربر ادمین (پیش‌فرض: اولین ادمین)}
                            {--password= : رمز جدید؛ اگر خالی باشد تصادفی ساخته می‌شود}';

    protected $description = 'بازنشانی رمز عبور یک کاربر ادمین (برای وقتی رمز فراموش شده است)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = $email
            ? User::withTrashed()->where('email', $email)->first()
            : User::where('role', User::ROLE_ADMIN)->orderBy('id')->first();

        if (! $user) {
            $this->error('کاربر ادمین پیدا نشد.');

            return self::FAILURE;
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $password = (string) $this->option('password');

        // رمز دستی باید حداقل ۱۰ کاراکتر و شامل حرف و رقم باشد
        if ($password !== '') {
            if (mb_strlen($password) < 10
                || ! preg_match('/\pL/u', $password)
                || ! preg_match('/\pN/u', $password)) {
                $this->error('رمز باید حداقل ۱۰ کاراکتر و شامل حرف و رقم باشد.');

                return self::FAILURE;
            }
        } else {
            $password = Str::password(12, symbols: false);
        }

        $user->forceFill([
            'password' => $password,
            'is_active' => true,
            'role' => User::ROLE_ADMIN,
        ])->save();

        $this->info('رمز عبور با موفقیت تغییر کرد.');
        $this->line("ایمیل: {$user->email}");
        $this->line("رمز جدید: {$password}");

        return self::SUCCESS;
    }
}
