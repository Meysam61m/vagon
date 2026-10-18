<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wagons') && ! Schema::hasColumn('wagons', 'previous_wagon_number')) {
            Schema::table('wagons', function (Blueprint $table) {
                // شماره واگن قبلی (در صورتی که شماره واگن پس از صدور صورتحساب اصلاح شده باشد)
                $table->string('previous_wagon_number', 50)->nullable()->after('wagon_number');
            });
        }

        if (Schema::hasTable('wagon_owner_logs')) {
            Schema::table('wagon_owner_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('wagon_owner_logs', 'change_type')) {
                    $table->string('change_type', 30)->default('owner')->after('billing_id');
                }
                if (! Schema::hasColumn('wagon_owner_logs', 'old_wagon_number')) {
                    $table->string('old_wagon_number', 50)->nullable()->after('change_type');
                }
                if (! Schema::hasColumn('wagon_owner_logs', 'new_wagon_number')) {
                    $table->string('new_wagon_number', 50)->nullable()->after('old_wagon_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wagon_owner_logs')) {
            Schema::table('wagon_owner_logs', function (Blueprint $table) {
                $cols = array_filter(
                    ['change_type', 'old_wagon_number', 'new_wagon_number'],
                    fn ($col) => Schema::hasColumn('wagon_owner_logs', $col)
                );
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('wagons') && Schema::hasColumn('wagons', 'previous_wagon_number')) {
            Schema::table('wagons', function (Blueprint $table) {
                $table->dropColumn('previous_wagon_number');
            });
        }
    }
};
