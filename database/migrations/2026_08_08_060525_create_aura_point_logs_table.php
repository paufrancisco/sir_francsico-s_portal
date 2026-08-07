<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aura_point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();

            // Delta lang ito (hal. +5, +1, -1) — hindi running total.
            $table->integer('points');

            // Optional label, ginagamit lalo na sa reset entries
            // (hal. "Reset" instead of blangkong bulk add).
            $table->string('note')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['section_id', 'student_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aura_point_logs');
    }
};