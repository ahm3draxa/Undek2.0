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
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('proposed_by_id')->nullable()->after('specialist_id')->constrained('users')->nullOnDelete();
            $table->date('delivery_date')->nullable()->after('estimated_days');
        });

        // Every quotation created before this migration was sent by the specialist —
        // backfill so proposer-based authorization (who may accept/reject) works for old rows too.
        DB::table('quotations')->whereNull('proposed_by_id')->update(['proposed_by_id' => DB::raw('specialist_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proposed_by_id');
            $table->dropColumn('delivery_date');
        });
    }
};
