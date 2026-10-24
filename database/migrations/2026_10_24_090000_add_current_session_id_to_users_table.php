<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "One active session per account" used to depend on sessions living in
     * the database (it deleted the other rows). Sessions now live in files so
     * public pages need no database connection at all, so the account itself
     * records which session is the current one instead.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('current_session_id', 128)->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_session_id');
        });
    }
};
