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
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('position')->constrained('positions')->nullOnDelete();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('skill_categories')->nullOnDelete();
        });

        // Carry forward whatever free-text values already exist, so nothing is silently lost.
        $this->migrateTextToLookup('employee_profiles', 'department', 'departments', 'department_id');
        $this->migrateTextToLookup('employee_profiles', 'position', 'positions', 'position_id');
        $this->migrateTextToLookup('skills', 'category', 'skill_categories', 'category_id');

        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['department', 'position']);
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    private function migrateTextToLookup(string $table, string $textColumn, string $lookupTable, string $fkColumn): void
    {
        $values = DB::table($table)->whereNotNull($textColumn)->distinct()->pluck($textColumn);

        foreach ($values as $value) {
            if (trim($value) === '') {
                continue;
            }

            $id = DB::table($lookupTable)->where('name', $value)->value('id');

            if (! $id) {
                $id = DB::table($lookupTable)->insertGetId([
                    'name' => $value,
                    'slug' => Str::slug($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table($table)->where($textColumn, $value)->update([$fkColumn => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('position')->nullable();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->string('category')->nullable();
        });

        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
