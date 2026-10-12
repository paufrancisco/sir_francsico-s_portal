<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['freedom_wall_likes', 'freedom_wall_replies'] as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'student_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->unsignedBigInteger('student_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Left as is: rows posted by the teacher have no student, so the column stays nullable.
    }
};
