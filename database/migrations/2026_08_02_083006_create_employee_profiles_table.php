<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contractor', 'intern'])->default('full_time');
            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->decimal('equity_shares', 8, 4)->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
