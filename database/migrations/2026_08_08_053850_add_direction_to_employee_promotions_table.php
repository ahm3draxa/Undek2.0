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
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->string('direction')->default('promotion')->after('new_position_id'); // promotion | demotion | lateral
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }
};
