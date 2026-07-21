<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            // Hiwalay sa 'status' (pending/resolved) — ito yung specific na desisyon
            // kapag na-resolve na: approved (na-apply yung proposed changes) o
            // rejected (walang binago sa grades).
            $table->enum('decision', ['approved', 'rejected'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('grade_corrections', function (Blueprint $table) {
            $table->dropColumn('decision');
        });
    }
};