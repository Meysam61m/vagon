<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_id')->constrained()->cascadeOnDelete();

            // یک واگن فقط یک بار قابل صورتحساب است؛ لغو فاکتور ردیف‌ها را آزاد می‌کند
            $table->foreignId('wagon_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('tariff_id')->nullable()->constrained()->nullOnDelete();

            // snapshot تعرفه در لحظه صدور
            $table->string('tariff_title');
            $table->unsignedBigInteger('tariff_rate');
            $table->decimal('tariff_capacity', 10, 2);
            $table->decimal('loaded_weight', 10, 2);
            $table->unsignedBigInteger('base_cost');
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_items');
    }
};
