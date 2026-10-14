<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attachments used to live on the public disk; new ones are stored
        // privately and served through an authorized route. Existing rows keep
        // 'public' so their files are still found where they were saved.
        Schema::table('quote_request_attachments', function (Blueprint $table) {
            $table->string('disk')->default('public')->after('quote_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('quote_request_attachments', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
