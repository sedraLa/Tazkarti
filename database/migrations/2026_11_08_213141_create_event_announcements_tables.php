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
        Schema::create('event_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('announcement_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient');
            $table->string('channel')->default('email');
            $table->string('status')->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['event_announcement_id', 'recipient', 'channel'], 'announcement_recipient_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_deliveries');
        Schema::dropIfExists('event_announcements');
    }
};
