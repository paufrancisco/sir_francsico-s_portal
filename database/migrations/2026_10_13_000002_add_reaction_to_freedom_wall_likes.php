<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('freedom_wall_likes', 'reaction')) {
            Schema::table('freedom_wall_likes', function (Blueprint $table) {
                // Existing likes stay hearts
                $table->string('reaction', 10)->default('heart')->after('author_first_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('freedom_wall_likes', function (Blueprint $table) {
            $table->dropColumn('reaction');
        });
    }
};
