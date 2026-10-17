<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_representative', function (Blueprint $table) {
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('representative_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['owner_id', 'representative_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_representative');
    }
};
