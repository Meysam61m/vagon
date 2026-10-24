<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wagons')) {
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

        if (Schema::hasTable('billings')) {
            $indexes = collect(Schema::getIndexes('billings'))->pluck('name')->all();

            Schema::table('billings', function (Blueprint $table) use ($indexes) {
                if (! in_array('billings_status_billing_date_index', $indexes, true)) {
                    $table->index(['status', 'billing_date'], 'billings_status_billing_date_index');
                }
            });
        }

        if (Schema::hasTable('cargo_requests')) {
            $indexes = collect(Schema::getIndexes('cargo_requests'))->pluck('name')->all();

            Schema::table('cargo_requests', function (Blueprint $table) use ($indexes) {
                if (! in_array('cargo_requests_status_request_date_index', $indexes, true)) {
                    $table->index(['status', 'request_date'], 'cargo_requests_status_request_date_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wagons')) {
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

        if (Schema::hasTable('billings')) {
            $indexes = collect(Schema::getIndexes('billings'))->pluck('name')->all();

            Schema::table('billings', function (Blueprint $table) use ($indexes) {
                if (in_array('billings_status_billing_date_index', $indexes, true)) {
                    $table->dropIndex('billings_status_billing_date_index');
                }
            });
        }

        if (Schema::hasTable('cargo_requests')) {
            $indexes = collect(Schema::getIndexes('cargo_requests'))->pluck('name')->all();

            Schema::table('cargo_requests', function (Blueprint $table) use ($indexes) {
                if (in_array('cargo_requests_status_request_date_index', $indexes, true)) {
                    $table->dropIndex('cargo_requests_status_request_date_index');
                }
            });
        }
    }
};
