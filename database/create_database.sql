-- ساخت دیتابیس سامانه مدیریت بارگیری واگن (MySQL / MariaDB)
-- این فایل را یک بار در phpMyAdmin (تب SQL) یا با دستور زیر اجرا کنید:
--
--     mysql -u root -p < database/create_database.sql
--
-- سپس در فایل .env مقدار DB_DATABASE را روی wagon_system بگذارید
-- و دستورهای زیر را اجرا کنید:
--
--     php artisan migrate
--     php artisan db:seed
--
-- توجه: ساخت جداول توسط migrationهای Laravel انجام می‌شود، نه این فایل.

CREATE DATABASE IF NOT EXISTS `wagon_system`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- (اختیاری) یک کاربر اختصاصی برای برنامه بسازید و رمز را عوض کنید:
--
-- CREATE USER 'wagon_user'@'localhost' IDENTIFIED BY 'یک-رمز-قوی-بگذارید';
-- GRANT ALL PRIVILEGES ON `wagon_system`.* TO 'wagon_user'@'localhost';
-- FLUSH PRIVILEGES;
--
-- سپس در .env:
--   DB_DATABASE=wagon_system
--   DB_USERNAME=wagon_user
--   DB_PASSWORD=یک-رمز-قوی-بگذارید
