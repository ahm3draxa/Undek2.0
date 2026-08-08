<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->foreignId('old_position_id')->nullable()->after('old_position')->constrained('positions')->nullOnDelete();
            $table->foreignId('new_position_id')->nullable()->after('new_position')->constrained('positions')->nullOnDelete();
        });

        foreach (['old_position' => 'old_position_id', 'new_position' => 'new_position_id'] as $textColumn => $fkColumn) {
            $values = DB::table('employee_promotions')->whereNotNull($textColumn)->distinct()->pluck($textColumn);

            foreach ($values as $value) {
                if (trim($value) === '') {
                    continue;
                }

                $id = DB::table('positions')->where('name', $value)->value('id');

                if (! $id) {
                    $id = DB::table('positions')->insertGetId([
                        'name' => $value,
                        'slug' => Str::slug($value),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('employee_promotions')->where($textColumn, $value)->update([$fkColumn => $id]);
            }
        }

        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->dropColumn(['old_position', 'new_position']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->string('old_position')->nullable();
            $table->string('new_position')->nullable();
        });

        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('old_position_id');
            $table->dropConstrainedForeignId('new_position_id');
        });
    }
};
