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

        $password = $this->option('password') ?: Str::password(12, symbols: false);

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
