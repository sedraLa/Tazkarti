<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('category')->default('regular');
            $table->decimal('price', 10, 2);
            $table->string('currency');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->unsignedInteger('max_per_order');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ticket_types ADD CONSTRAINT chk_ticket_inventory CHECK ((reserved_quantity + sold_quantity) <= quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
