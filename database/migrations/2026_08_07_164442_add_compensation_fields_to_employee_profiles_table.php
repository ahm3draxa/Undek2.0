<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('compensation_type')->default('fixed_salary')->after('base_salary'); // hourly | fixed_salary | fixed_project_price | project_percentage
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('compensation_type');
            $table->decimal('fixed_project_price', 10, 2)->nullable()->after('hourly_rate');
            $table->decimal('project_percentage', 5, 2)->nullable()->after('fixed_project_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['compensation_type', 'hourly_rate', 'fixed_project_price', 'project_percentage']);
        });
    }
};
