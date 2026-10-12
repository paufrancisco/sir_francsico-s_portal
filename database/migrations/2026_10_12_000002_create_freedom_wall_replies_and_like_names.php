<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('freedom_wall_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('freedom_wall_posts')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('author_first_name', 40)->nullable();
            $table->string('body', 200);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('freedom_wall_likes', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('client_id');
            $table->string('author_first_name', 40)->nullable()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freedom_wall_replies');

        Schema::table('freedom_wall_likes', function (Blueprint $table) {
            $table->dropColumn(['student_id', 'author_first_name']);
        });
    }
};
