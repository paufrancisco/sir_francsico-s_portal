<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->enum('category', ['quiz', 'exam', 'project', 'activity', 'prelim', 'midterm', 'final'])->default('quiz');
            $table->string('title')->nullable(); // e.g. "Quiz 1", "TP Project"
            $table->decimal('score', 6, 2);
            $table->decimal('max_score', 6, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};