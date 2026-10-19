<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (Schema::hasTable('cargo_requests') && Schema::hasColumn('cargo_requests', 'owner_id')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `cargo_requests` MODIFY `owner_id` BIGINT UNSIGNED NULL');
            } else {
                Schema::table('cargo_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('owner_id')->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('wagon_owner_logs') && Schema::hasColumn('wagon_owner_logs', 'old_owner_id')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `wagon_owner_logs` MODIFY `old_owner_id` BIGINT UNSIGNED NULL');
            } else {
                Schema::table('wagon_owner_logs', function (Blueprint $table) {
                    $table->unsignedBigInteger('old_owner_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // حفظ قابلیت NULL برای صاحب کالای ناشناس
    }
};
