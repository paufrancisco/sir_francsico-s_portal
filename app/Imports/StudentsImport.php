<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    /** Bagong estudyante (may bagong password) */
    public array $imported = [];
    /** Existing sa section na ito: walang ginalaw maliban sa pangalan kung nag-iba */
    public array $updated = [];
    /** Existing sa ibang section: hindi ginalaw */
    public array $skipped = [];

    public function __construct(private int $sectionId) {}

    // "2000503959" -> "503959". Ang 6-digit ay hindi ginagalaw.
    private function normalizeNumber($value): string
    {
        if (is_numeric($value)) {
            $value = sprintf('%.0f', $value); // iwas 2.0005E+9 galing Excel
        }
        $number = trim((string) $value);

        if (strlen($number) === 10 && str_starts_with($number, '2000')) {
            return substr($number, 4);
        }

        return $number;
    }

    public function collection($rows)
    {
        foreach ($rows as $row) {
            $raw = trim((string) ($row['student_number'] ?? ''));
            $name = trim((string) ($row['full_name'] ?? ''));

            if ($raw === '' || $name === '') {
                continue;
            }

            $number = $this->normalizeNumber($row['student_number']);

            // Hanapin gamit ang na-normalize at ang eksaktong numero sa file
            $student = Student::whereIn('student_number', array_unique([$number, $raw]))->first();

            if ($student) {
                if ($student->section_id && (int) $student->section_id !== $this->sectionId) {
                    $this->skipped[] = [
                        'student_number' => $student->student_number,
                        'full_name' => $student->full_name,
                        'reason' => 'Already in another section',
                    ];
                    continue;
                }

                // Pangalan lang ang pwedeng mabago. Password, photo, frame, grades: hindi ginagalaw.
                $updates = [];
                if ($student->full_name !== $name) {
                    $updates['full_name'] = $name;
                }
                if (! $student->section_id) {
                    $updates['section_id'] = $this->sectionId;
                }
                if ($updates) {
                    $student->update($updates);
                }

                $this->updated[] = [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                ];
                continue;
            }

            $plainPassword = Str::upper(Str::random(6));

            $student = Student::create([
                'student_number' => $number,
                'full_name' => $name,
                'section_id' => $this->sectionId,
                'password' => $plainPassword,
                'password_changed_at' => now(),
            ]);

            $this->imported[] = [
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'password' => $plainPassword,
            ];
        }
    }
}
