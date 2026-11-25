<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 40);
            $table->string('period', 20);
            $table->unsignedSmallInteger('student_count');
            $table->boolean('dry_run')->default(false);
            $table->timestamps();
        });

        // Reminder: keep RLS consistent with the other Supabase public tables
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bulk_import_logs ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_import_logs');
    }
};
