<?php

namespace App\Imports;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GradesImport implements ToCollection
{
    public array $skipped = [];
    public array $duplicates = [];
    public int $importedCount = 0;

    protected array $seenStudentNumbers = [];

    public function __construct(protected int $sectionId, protected string $period)
    {
    }

    public function collection(Collection $rows)
    {
        $header = $rows->first();
        $columnDefs = [];

        foreach ($header as $idx => $cell) {
            if ($idx === 0) {
                continue; // Column A = student number
            }

            if (preg_match('/^(long\s*quiz|tp|exam)\s*:\s*(.+?)\s*\((\d+(?:\.\d+)?)\)$/i', trim((string) $cell), $m)) {
                $catRaw = strtolower(trim($m[1]));
                $category = match (true) {
                    str_contains($catRaw, 'long') => 'long_quiz',
                    str_contains($catRaw, 'tp') => 'tp',
                    str_contains($catRaw, 'exam') => 'exam',
                    default => null,
                };

                if ($category) {
                    $columnDefs[$idx] = [
                        'category' => $category,
                        'title' => trim($m[2]),
                        'max_score' => (float) $m[3],
                    ];
                }
            }
        }

        // Isang query lang para kunin lahat ng estudyante ng section (imbes na isa-isa kada row)
        $students = Student::where('section_id', $this->sectionId)
            ->get()
            ->keyBy(fn ($s) => strtolower(trim($s->student_number)));

        $now = now();
        $userId = auth()->id();
        $toInsert = []; // keyed para awtomatikong mapalitan ang duplicate rows (huling row lang manalo)

        foreach ($rows->skip(1) as $row) {
            $studentNumberRaw = trim((string) ($row[0] ?? ''));
            if ($studentNumberRaw === '') {
                continue;
            }

            $lookupKey = strtolower($studentNumberRaw);

            if (isset($this->seenStudentNumbers[$lookupKey])) {
                if (! in_array($studentNumberRaw, $this->duplicates, true)) {
                    $this->duplicates[] = $studentNumberRaw;
                }
            } else {
                $this->seenStudentNumbers[$lookupKey] = true;
            }

            $student = $students->get($lookupKey);

            if (! $student) {
                if (! in_array($studentNumberRaw, $this->skipped, true)) {
                    $this->skipped[] = $studentNumberRaw;
                }
                continue;
            }

            foreach ($columnDefs as $idx => $def) {
                $score = $row[$idx] ?? null;
                $score = ($score === null || $score === '') ? 0 : $score;

                $key = $student->id . '|' . $def['category'] . '|' . $def['title'];

                $toInsert[$key] = [
                    'student_id' => $student->id,
                    'section_id' => $this->sectionId,
                    'category' => $def['category'],
                    'period' => $this->period,
                    'title' => $def['title'],
                    'score' => (float) $score,
                    'max_score' => $def['max_score'],
                    'recorded_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->importedCount = $students->count() - count($this->skipped);

        // I-insert lahat nang sabay-sabay sa mga chunks ng 200 (mabilis, konting queries lang)
        foreach (array_chunk(array_values($toInsert), 200) as $chunk) {
            Grade::insert($chunk);
        }
    }
}