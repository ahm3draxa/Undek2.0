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
        Schema::table('compensation_increments', function (Blueprint $table) {
            $table->string('direction')->default('increase')->after('type'); // increase | decrease
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compensation_increments', function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }
};
