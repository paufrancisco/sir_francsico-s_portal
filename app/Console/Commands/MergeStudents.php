<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeStudents extends Command
{
    protected $signature = 'students:merge {keep : student_number na itatago} {remove : student_number na ima-merge at buburahin} {--dry-run}';

    protected $description = 'I-merge ang dalawang account ng iisang estudyante (sections, grades, corrections, atbp.) sa isa.';

    private array $tables = [
        'seats', 'attendance_records', 'grades', 'locked_section_logs', 'questions',
        'messages', 'chat_sessions', 'grade_corrections', 'appointments',
        'aura_point_logs', 'student_login_logs', 'student_activities', 'freedom_wall_posts',
    ];

    public function handle(): int
    {
        $keepNo = $this->argument('keep');
        $removeNo = $this->argument('remove');
        $dry = (bool) $this->option('dry-run');

        $remove = Student::where('student_number', $removeNo)->first();
        $keep = Student::where('student_number', $keepNo)->first();

        if (! $remove) {
            $this->error("Walang student na {$removeNo}.");
            return 1;
        }
        if ($keep && $keep->id === $remove->id) {
            $this->error('Iisang account lang ang dalawa.');
            return 1;
        }

        DB::beginTransaction();

        try {
            if (! $keep) {
                $remove->update(['student_number' => $keepNo]);
                $remove->sections()->syncWithoutDetaching(array_filter([$remove->section_id]));
                $this->info("Walang existing na {$keepNo}, kaya ni-rename lang ang {$removeNo} -> {$keepNo}.");
                $final = $remove;
            } else {
                $this->line("KEEP  : #{$keep->id} {$keep->student_number} {$keep->full_name} (section_id {$keep->section_id})");
                $this->line("REMOVE: #{$remove->id} {$remove->student_number} {$remove->full_name} (section_id {$remove->section_id})");

                // 1. Sections
                $sectionIds = DB::table('section_student')->where('student_id', $remove->id)->pluck('section_id')->all();
                if ($remove->section_id) {
                    $sectionIds[] = $remove->section_id;
                }
                $keep->sections()->syncWithoutDetaching(array_values(array_unique($sectionIds)));

                if (! $keep->section_id && $remove->section_id) {
                    $keep->section_id = $remove->section_id;
                }

                // 2. Photo, aura
                if (! $keep->photo_path && $remove->photo_path) {
                    $keep->photo_path = $remove->photo_path;
                }
                $keep->aura_points = (int) $keep->aura_points + (int) $remove->aura_points;
                $keep->save();

                // 3. Ilipat lahat ng rows na nakakabit sa student
                foreach ($this->tables as $t) {
                    if (! Schema::hasTable($t) || ! Schema::hasColumn($t, 'student_id')) {
                        continue;
                    }
                    $n = DB::table($t)->where('student_id', $remove->id)->update(['student_id' => $keep->id]);
                    if ($n) {
                        $this->line("  {$t}: {$n} row(s) inilipat");
                    }
                }

                // 4. Burahin ang duplicate
                $remove->delete();
                $final = $keep;
            }

            $names = $final->sections()->get()->map(fn ($s) => trim($s->name . ' ' . $s->subject))->join(' | ');
            $this->info("Final sections ni {$final->full_name}: {$names}");

            if ($dry) {
                DB::rollBack();
                $this->warn('DRY RUN: walang binago sa database.');
            } else {
                DB::commit();
                $this->info('Tapos na ang merge.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed, walang binago: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
