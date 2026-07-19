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

    public function __construct(protected int $sectionId)
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

        foreach ($rows->skip(1) as $row) {
            $studentNumber = trim((string) ($row[0] ?? ''));
            if ($studentNumber === '') {
                continue;
            }

            if (isset($this->seenStudentNumbers[$studentNumber])) {
                if (! in_array($studentNumber, $this->duplicates, true)) {
                    $this->duplicates[] = $studentNumber;
                }
            } else {
                $this->seenStudentNumbers[$studentNumber] = true;
            }

            $student = Student::where('section_id', $this->sectionId)
                ->where('student_number', $studentNumber)
                ->first();

            if (! $student) {
                if (! in_array($studentNumber, $this->skipped, true)) {
                    $this->skipped[] = $studentNumber;
                }
                continue;
            }

            $this->importedCount++;

            foreach ($columnDefs as $idx => $def) {
                $score = $row[$idx] ?? null;
                if ($score === null || $score === '') {
                    continue;
                }

                Grade::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'section_id' => $this->sectionId,
                        'category' => $def['category'],
                        'title' => $def['title'],
                    ],
                    [
                        'score' => (float) $score,
                        'max_score' => $def['max_score'],
                        'recorded_by' => auth()->id(),
                    ]
                );
            }
        }
    }
}