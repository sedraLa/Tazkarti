<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('channel');
            $table->string('notification_type');
            $table->string('recipient');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'channel', 'notification_type'], 'notif_booking_channel_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
