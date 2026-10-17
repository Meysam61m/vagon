<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wagons', function (Blueprint $table) {
            // شرح کالای هر واگن (اختیاری) — مثلاً «گندم»، «سیمان فله»، «کاشی»
            $table->string('cargo_description', 255)->nullable()->after('wagon_number');
        });
    }

    public function down(): void
    {
        Schema::table('wagons', function (Blueprint $table) {
            $table->dropColumn('cargo_description');
        });
    }
};
