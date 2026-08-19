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
        Schema::table('milestones', function (Blueprint $table) {
            // Who's actually responsible for this specific milestone — lets a project
            // lead delegate individual milestones (e.g. to a specialist's intern, or
            // to whichever department's work it is) without that person needing the
            // broader "manage milestones" permission.
            $table->foreignId('assigned_to')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
