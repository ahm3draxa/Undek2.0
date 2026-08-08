<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('old_position')->nullable();
            $table->string('new_position');
            $table->decimal('old_salary', 12, 2)->nullable();
            $table->decimal('new_salary', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_promotions');
    }
};
