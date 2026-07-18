<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question_text');
            $table->text('answer_text')->nullable();
            $table->boolean('answered')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps(); // created_at = kelan tinanong/pinost
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};