<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('full_name');
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('password'); // shared login password (hashed)
            $table->string('grades_password')->nullable(); // password para sa locked grades/quiz section
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};