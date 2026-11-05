<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->restrictOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('event_type');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('event_type');
            $table->index('start_at');
            $table->index('status');
            $table->index(['status', 'start_at']);
            $table->index(['status', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
