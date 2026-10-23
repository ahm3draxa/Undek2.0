<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 'form' — submitted through the website; 'manual' — the record behind a
     * project staff created directly (phone/WhatsApp clients, repeat work,
     * internal projects), so every project still has the same quote →
     * quotation → project chain that reports and permissions rely on.
     */
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('source', 16)->default('form')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
