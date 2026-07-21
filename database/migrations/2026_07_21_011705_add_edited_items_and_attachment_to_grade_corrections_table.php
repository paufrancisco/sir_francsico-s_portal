<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            $table->string('period')->nullable()->after('type');
            $table->json('edited_items')->nullable()->after('notes');
            $table->string('attachment_path')->nullable()->after('edited_items');
        });
    }

    public function down(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            $table->dropColumn(['period', 'edited_items', 'attachment_path']);
        });
    }
};