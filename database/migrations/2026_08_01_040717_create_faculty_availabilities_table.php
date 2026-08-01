<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_availabilities', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            // true while no appointment has been booked against this slot yet
            $table->boolean('is_booked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'is_active', 'is_booked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_availabilities');
    }
};