<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('absent');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete(); // admin/teacher
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date']); // isang record lang per student per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};