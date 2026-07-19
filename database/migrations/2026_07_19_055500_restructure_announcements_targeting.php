<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('body');
        });

        Schema::create('announcement_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        DB::table('announcements')->whereNull('section_id')->update(['is_global' => true]);

        DB::table('announcements')->whereNotNull('section_id')->orderBy('id')->each(function ($a) {
            DB::table('announcement_section')->insert([
                'announcement_id' => $a->id,
                'section_id' => $a->section_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::table('announcement_section')->orderBy('id')->each(function ($row) {
            DB::table('announcements')->where('id', $row->announcement_id)->update(['section_id' => $row->section_id]);
        });

        Schema::dropIfExists('announcement_section');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};