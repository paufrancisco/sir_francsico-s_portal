<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('grade_corrections', 'attachment_path')) {
            Schema::table('grade_corrections', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('notes');
            });
        }

        if (!Schema::hasTable('grade_correction_items')) {
            Schema::create('grade_correction_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_correction_id')->constrained('grade_corrections')->cascadeOnDelete();
                $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();

                $table->decimal('current_score', 8, 2);
                $table->decimal('proposed_score', 8, 2);
                $table->decimal('max_score', 8, 2);

                $table->timestamps();

                $table->unique(['grade_correction_id', 'grade_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_correction_items');

        if (Schema::hasColumn('grade_corrections', 'attachment_path')) {
            Schema::table('grade_corrections', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }
    }
};