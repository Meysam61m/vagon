<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique();
            $table->foreignId('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('representative_id')->constrained()->restrictOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date');
            $table->string('request_date_shamsi', 10);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['request_date', 'status']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_requests');
    }
};
