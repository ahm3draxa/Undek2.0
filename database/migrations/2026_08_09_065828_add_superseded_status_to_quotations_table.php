<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 'superseded' = this proposal was replaced by a newer one before anyone
        // explicitly accepted/declined it (the new propose-anytime negotiation flow).
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('sent', 'accepted', 'rejected', 'superseded') NOT NULL DEFAULT 'sent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE quotations SET status = 'rejected' WHERE status = 'superseded'");
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('sent', 'accepted', 'rejected') NOT NULL DEFAULT 'sent'");
    }
};
