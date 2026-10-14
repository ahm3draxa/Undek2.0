<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->string('mailbox', 32); // key from config/inbox.php, e.g. info / support
            $table->string('direction', 8)->default('in'); // in | out (replies sent from the portal)
            $table->unsignedBigInteger('uid')->nullable(); // IMAP UID, for incremental fetching
            $table->string('message_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('to')->nullable();
            $table->text('cc')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('inbox_messages')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mailbox', 'uid']);
            $table->index(['mailbox', 'direction', 'received_at']);
        });

        Schema::create('inbox_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_message_id')->constrained('inbox_messages')->cascadeOnDelete();
            $table->string('disk')->nullable(); // null when the file was too large or blocked and wasn't kept
            $table->string('path')->nullable();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_attachments');
        Schema::dropIfExists('inbox_messages');
    }
};
