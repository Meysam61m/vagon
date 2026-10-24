<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->string('billing_number', 30)->unique();
            $table->foreignId('owner_id')->constrained()->restrictOnDelete();
            $table->foreignId('representative_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();

            // snapshot هویتی در لحظه صدور (تغییر نام بعدی، فاکتور قدیمی را عوض نمی‌کند)
            $table->string('owner_name');
            $table->string('representative_name')->nullable();

            $table->date('billing_date');
            $table->string('billing_date_shamsi', 10);
            $table->unsignedBigInteger('base_amount')->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status', 20)->default('issued');
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['billing_date', 'status']);
            $table->index(['status', 'billing_date']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
