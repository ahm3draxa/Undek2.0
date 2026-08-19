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
        Schema::table('messages', function (Blueprint $table) {
            // 'message' = something a person typed; 'event' = an automatic log entry
            // (quotation sent/accepted/declined, etc.) rendered differently in the UI.
            $table->string('type', 20)->default('message')->after('user_id');
            // 'client' = client-visible thread (quote request / project chat); 'team' =
            // internal-only project channel, never shown to the client.
            $table->string('channel', 20)->default('client')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'channel']);
        });
    }
};
