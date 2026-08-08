<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_request_skill', function (Blueprint $table) {
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['quote_request_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_request_skill');
    }
};
