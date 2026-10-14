<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is now enforced for new sign-ups. Accounts that
     * existed before that are grandfathered in as verified, so switching the
     * check on doesn't suddenly lock out every current client and employee.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible: there's no record of which accounts were unverified.
    }
};
