<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 'superseded' = this proposal was replaced by a newer one before anyone
        // explicitly accepted/declined it (the new propose-anytime negotiation flow).
        $this->setStatuses(['sent', 'accepted', 'rejected', 'superseded']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE quotations SET status = 'rejected' WHERE status = 'superseded'");
        $this->setStatuses(['sent', 'accepted', 'rejected']);
    }

    /**
     * MODIFY is MySQL-only; other drivers (SQLite in the test suite) go through
     * the schema builder, which rebuilds the column's check constraint instead.
     */
    private function setStatuses(array $statuses): void
    {
        if (DB::getDriverName() === 'mysql') {
            $list = implode(', ', array_map(fn ($status) => "'$status'", $statuses));
            DB::statement("ALTER TABLE quotations MODIFY status ENUM($list) NOT NULL DEFAULT 'sent'");

            return;
        }

        Schema::table('quotations', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('sent')->change();
        });
    }
};
