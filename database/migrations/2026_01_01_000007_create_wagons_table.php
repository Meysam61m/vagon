<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wagons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_request_id')->constrained()->cascadeOnDelete();
            $table->string('wagon_number', 50);
            $table->foreignId('tariff_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('loaded_weight', 10, 2)->nullable();
            $table->date('loaded_on')->nullable();
            $table->boolean('is_settled')->default(false);
            $table->timestamps();

            $table->unique(['cargo_request_id', 'wagon_number']);
            $table->index(['is_settled', 'loaded_weight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wagons');
    }
};
