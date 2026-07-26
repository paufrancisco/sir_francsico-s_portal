<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->string('period')->default('prelim')->after('section_id'); // prelim, midterm, prefinal, finals
            $table->boolean('has_quiz')->default(false)->after('status');
            $table->unsignedInteger('quiz_items')->nullable()->after('has_quiz'); // "up to ilan" / max score, kapag may quiz
            $table->boolean('has_tp')->default(false)->after('quiz_items');
            $table->unsignedInteger('sort_order')->default(0)->after('has_tp');
            $table->timestamp('archived_at')->nullable()->after('sort_order');

            $table->index(['section_id', 'period', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['section_id', 'period', 'archived_at']);
            $table->dropColumn(['period', 'has_quiz', 'quiz_items', 'has_tp', 'sort_order', 'archived_at']);
        });
    }
};
