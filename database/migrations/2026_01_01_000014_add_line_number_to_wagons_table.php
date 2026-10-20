<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wagons') || Schema::hasColumn('wagons', 'line_number')) {
            return;
        }

        Schema::table('wagons', function (Blueprint $table) {
            $table->string('line_number', 50)->nullable()->after('cargo_description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wagons') || ! Schema::hasColumn('wagons', 'line_number')) {
            return;
        }

        Schema::table('wagons', function (Blueprint $table) {
            $table->dropColumn('line_number');
        });
    }
};
