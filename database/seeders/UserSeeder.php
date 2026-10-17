<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('app.seed_admin_email', 'admin@wagon.local');
        $adminPassword = config('app.seed_admin_password');
        $generated = false;

        // رمز ضعیف از .env پذیرفته نمی‌شود؛ به‌جایش رمز تصادفی ساخته می‌شود
        if (filled($adminPassword) && mb_strlen((string) $adminPassword) < 10) {
            $this->command?->warn('SEED_ADMIN_PASSWORD کوتاه‌تر از ۱۰ کاراکتر است و نادیده گرفته شد.');

            $adminPassword = null;
        }

        if (blank($adminPassword)) {
            $adminPassword = Str::password(12, symbols: false);
            $generated = true;
        }

        $admin = User::withTrashed()->firstOrNew(['email' => $adminEmail]);
        $admin->fill([
            'name' => 'مدیر سیستم',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // اگر کاربر از قبل وجود دارد، رمزش را در هر بار seed عوض نکن
        if (! $admin->exists) {
            $admin->password = $adminPassword;
        }

        $admin->deleted_at = null;
        $admin->save();

        if ($generated && $admin->wasRecentlyCreated) {
            $this->command?->warn("کاربر ادمین ساخته شد: {$adminEmail}");
            $this->command?->warn("رمز عبور اولیه: {$adminPassword}");
            $this->command?->warn('این رمز را در جای امن نگه دارید و پس از اولین ورود تغییر دهید.');
        }

        $operatorEmail = config('app.seed_operator_email', 'operator@wagon.local');

        if (! User::withTrashed()->where('email', $operatorEmail)->exists()) {
            $operatorPassword = Str::password(12, symbols: false);

            User::create([
                'name' => 'اپراتور نمونه',
                'email' => $operatorEmail,
                'password' => $operatorPassword,
                'role' => User::ROLE_OPERATOR,
                'is_active' => true,
            ]);

            $this->command?->warn("کاربر اپراتور ساخته شد: {$operatorEmail}");
            $this->command?->warn("رمز عبور اولیه: {$operatorPassword}");
        }
    }
}
