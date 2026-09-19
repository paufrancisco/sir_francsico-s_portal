<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classmate_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('students')->cascadeOnDelete();
            $table->string('body', 300);
            $table->timestamp('hidden_at')->nullable(); // set this to hide a message from the portal
            $table->timestamps();

            $table->index(['recipient_id', 'hidden_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classmate_messages');
    }
};