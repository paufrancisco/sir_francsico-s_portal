<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public array $imported = [];

    public function __construct(private int $sectionId) {}

    public function collection($rows)
    {
        foreach ($rows as $row) {
            $plainPassword = Str::upper(Str::random(6));

            $student = Student::updateOrCreate(
                ['student_number' => trim($row['student_number'])],
                [
                    'full_name' => trim($row['full_name']),
                    'section_id' => $this->sectionId,
                    'password' => $plainPassword,
                    'password_changed_at' => now(),
                ]
            );

            $this->imported[] = [
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'password' => $plainPassword,
            ];
        }
    }
}