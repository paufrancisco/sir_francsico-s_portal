<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('freedom_wall_posts', function (Blueprint $table) {
            $table->string('author_first_name', 40)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('freedom_wall_posts', function (Blueprint $table) {
            $table->dropColumn('author_first_name');
        });
    }
};
