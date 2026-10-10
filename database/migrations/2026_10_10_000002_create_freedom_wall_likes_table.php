<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freedom_wall_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('freedom_wall_posts')->cascadeOnDelete();
            $table->string('client_id', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['post_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freedom_wall_likes');
    }
};
