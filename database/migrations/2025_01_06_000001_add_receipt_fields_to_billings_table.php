<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billings')) {
            return;
        }

        Schema::table('billings', function (Blueprint $table) {
            if (! Schema::hasColumn('billings', 'receipt_number')) {
                $table->string('receipt_number', 100)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('billings', 'receipt_date_shamsi')) {
                $table->string('receipt_date_shamsi', 20)->nullable()->after('receipt_number');
            }
            if (! Schema::hasColumn('billings', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('receipt_date_shamsi');
            }
            if (! Schema::hasColumn('billings', 'receipt_amount')) {
                $table->unsignedBigInteger('receipt_amount')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('billings', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('receipt_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('billings')) {
            return;
        }

        Schema::table('billings', function (Blueprint $table) {
            $cols = array_filter(
                ['receipt_number', 'receipt_date_shamsi', 'bank_name', 'receipt_amount', 'settled_at'],
                fn (string $col) => Schema::hasColumn('billings', $col)
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
