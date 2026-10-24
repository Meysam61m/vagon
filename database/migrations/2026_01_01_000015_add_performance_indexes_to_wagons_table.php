<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wagons')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('wagons'))->pluck('name')->all();

        Schema::table('wagons', function (Blueprint $table) use ($indexes) {
            if (! in_array('wagons_wagon_number_index', $indexes, true)) {
                $table->index('wagon_number', 'wagons_wagon_number_index');
            }
            if (! in_array('wagons_loaded_on_index', $indexes, true)) {
                $table->index('loaded_on', 'wagons_loaded_on_index');
            }
            if (! in_array('wagons_is_settled_loaded_on_index', $indexes, true)) {
                $table->index(['is_settled', 'loaded_on'], 'wagons_is_settled_loaded_on_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wagons')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('wagons'))->pluck('name')->all();

        Schema::table('wagons', function (Blueprint $table) use ($indexes) {
            if (in_array('wagons_is_settled_loaded_on_index', $indexes, true)) {
                $table->dropIndex('wagons_is_settled_loaded_on_index');
            }
            if (in_array('wagons_loaded_on_index', $indexes, true)) {
                $table->dropIndex('wagons_loaded_on_index');
            }
            if (in_array('wagons_wagon_number_index', $indexes, true)) {
                $table->dropIndex('wagons_wagon_number_index');
            }
        });
    }
};
