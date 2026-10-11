<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['section_id', 'student_id']);
        });

        // Ilipat ang kasalukuyang students.section_id sa pivot para walang mawala.
        $now = now()->toDateTimeString();
        DB::insert(
            'INSERT INTO section_student (section_id, student_id, created_at, updated_at)
             SELECT section_id, id, ?, ? FROM students WHERE section_id IS NOT NULL',
            [$now, $now]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('section_student');
    }
};
