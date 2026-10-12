<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->string('caption', 500)->nullable();
            $table->string('layout', 20)->default('auto');
            $table->string('author_first_name', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('portal_post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('portal_posts')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
        });

        Schema::create('portal_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('portal_posts')->cascadeOnDelete();
            // One reaction per person per post: 's<student id>' or 'teacher'
            $table->string('actor', 24);
            $table->string('device_id', 64)->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('author_first_name', 40)->nullable();
            $table->string('reaction', 10)->default('heart');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_id', 'actor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_post_reactions');
        Schema::dropIfExists('portal_post_images');
        Schema::dropIfExists('portal_posts');
    }
};
