<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            if (! Schema::hasColumn('grade_corrections', 'review_comment')) {
                $table->text('review_comment')->nullable()->after('decision');
            }
            if (! Schema::hasColumn('grade_corrections', 'review_attachment_path')) {
                $table->string('review_attachment_path')->nullable()->after('review_comment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            $table->dropColumn(['review_comment', 'review_attachment_path']);
        });
    }
};
