<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wagons')) {
            Schema::table('wagons', function (Blueprint $table) {
                if (! Schema::hasColumn('wagons', 'owner_id')) {
                    $table->foreignId('owner_id')->nullable()->after('cargo_request_id')->constrained('owners')->nullOnDelete();
                }
                if (! Schema::hasColumn('wagons', 'previous_owner_id')) {
                    $table->foreignId('previous_owner_id')->nullable()->after('owner_id')->constrained('owners')->nullOnDelete();
                }
                if (! Schema::hasColumn('wagons', 'owner_change_note')) {
                    $table->text('owner_change_note')->nullable()->after('is_settled');
                }
                if (! Schema::hasColumn('wagons', 'owner_changed_at')) {
                    $table->timestamp('owner_changed_at')->nullable()->after('owner_change_note');
                    $table->index('owner_changed_at');
                }
                if (! Schema::hasColumn('wagons', 'owner_changed_by')) {
                    $table->foreignId('owner_changed_by')->nullable()->after('owner_changed_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('wagons', 'owner_change_count')) {
                    $table->unsignedInteger('owner_change_count')->default(0)->after('owner_changed_by');
                }
            });

            // مقداردهی اولیه owner_id برای واگن‌های موجود از روی درخواست بارگیری آن‌ها
            $requests = DB::table('cargo_requests')->pluck('owner_id', 'id');

            foreach ($requests as $requestId => $ownerId) {
                DB::table('wagons')
                    ->where('cargo_request_id', $requestId)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $ownerId]);
            }
        }

        // جدول تاریخچه ویرایش‌های صاحب کالای واگن‌ها (برای گزارش و داشبورد مدیر)
        if (! Schema::hasTable('wagon_owner_logs')) {
            Schema::create('wagon_owner_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wagon_id')->constrained('wagons')->cascadeOnDelete();
                $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
                $table->foreignId('old_owner_id')->constrained('owners')->restrictOnDelete();
                $table->foreignId('new_owner_id')->constrained('owners')->restrictOnDelete();
                $table->string('old_owner_name');
                $table->string('new_owner_name');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note');
                $table->timestamps();

                $table->index(['wagon_id', 'created_at']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wagon_owner_logs');

        if (Schema::hasTable('wagons') && Schema::hasColumn('wagons', 'owner_change_count')) {
            Schema::table('wagons', function (Blueprint $table) {
                $table->dropForeign(['owner_id']);
                $table->dropForeign(['previous_owner_id']);
                $table->dropForeign(['owner_changed_by']);
                $table->dropIndex(['owner_changed_at']);
                $table->dropColumn([
                    'owner_id',
                    'previous_owner_id',
                    'owner_change_note',
                    'owner_changed_at',
                    'owner_changed_by',
                    'owner_change_count',
                ]);
            });
        }
    }
};
