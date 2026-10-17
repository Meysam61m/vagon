<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('title');
            $table->string('unit')->default('واگن');
            $table->unsignedBigInteger('rate');
            $table->unsignedTinyInteger('category')->default(2);
            $table->decimal('capacity', 10, 2);
            $table->string('equipment_type', 20)->default('crane');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'equipment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
