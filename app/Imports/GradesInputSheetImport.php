<?php

namespace App\Imports;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GradesInputSheetImport implements ToCollection
{
    // Column (0-based) kung saan nagsisimula ang bawat period block sa
    // "Input" sheet ng STI P60 template.
    private const PERIOD_BLOCK_START = [
        'prelim' => 6,
        'midterm' => 19,
        'prefinal' => 32,
        'finals' => 45,
    ];

    // Relative offset (mula sa block start) ng bawat item sa loob ng isang
    // period block: PTL1-4, TTL(skip), Q1-4, TTL(skip), EX, EQV(skip).
    private const OFFSET_PTL = [0, 1, 2, 3];
    private const OFFSET_QUIZ = [5, 6, 7, 8];
    private const OFFSET_EXAM = 10;

    // 0-based row index (sa collection) kung saan nakalagay ang max score
    // ng bawat item - ito yung row na may "10", "20", "50" atbp.
    private const MAX_SCORE_ROW_INDEX = 5;

    // Saan nagsisimula ang data ng mga estudyante (0-based row index).
    private const DATA_START_ROW_INDEX = 7;

    private const STUDENT_NUMBER_COL = 3; // Column D
    private const STUDENT_NAME_COL = 5;   // Column F

    protected array $seenStudentNumbers = [];
    protected array $matchedStudentIds = [];

    public function __construct(protected int $sectionId, protected GradesImport $result)
    {
    }

    public function collection(Collection $rows)
    {
        $maxScoreRow = $rows->get(self::MAX_SCORE_ROW_INDEX);

        if (! $maxScoreRow) {
            return; // hindi ito valid na "Input" sheet, wala tayong magagawa
        }

        $students = Student::where('section_id', $this->sectionId)
            ->get()
            ->keyBy(fn ($s) => strtolower(trim($s->student_number)));

        $now = now();
        $userId = auth()->id();
        $toInsert = []; // keyed para awtomatikong mapalitan ang duplicate rows

        foreach ($rows->slice(self::DATA_START_ROW_INDEX) as $row) {
            $studentNumberRaw = trim((string) ($row[self::STUDENT_NUMBER_COL] ?? ''));

            if ($studentNumberRaw === '') {
                continue; // blangkong row lang sa template, hindi error
            }

            $lookupKey = strtolower($studentNumberRaw);

            if (isset($this->seenStudentNumbers[$lookupKey])) {
                if (! in_array($studentNumberRaw, $this->result->duplicates, true)) {
                    $this->result->duplicates[] = $studentNumberRaw;
                }
            } else {
                $this->seenStudentNumbers[$lookupKey] = true;
            }

            $student = $students->get($lookupKey);

            if (! $student) {
                if (! in_array($studentNumberRaw, $this->result->skipped, true)) {
                    $this->result->skipped[] = $studentNumberRaw;
                }
                continue;
            }

            $this->matchedStudentIds[$student->id] = true;

            foreach (self::PERIOD_BLOCK_START as $period => $blockStart) {
                foreach (self::OFFSET_PTL as $i => $offset) {
                    $this->queueItem($toInsert, $student->id, $period, 'tp', 'PT/Lab ' . ($i + 1), $row, $maxScoreRow, $blockStart + $offset, $now, $userId);
                }

                foreach (self::OFFSET_QUIZ as $i => $offset) {
                    $this->queueItem($toInsert, $student->id, $period, 'long_quiz', 'Quiz ' . ($i + 1), $row, $maxScoreRow, $blockStart + $offset, $now, $userId);
                }

                $this->queueItem($toInsert, $student->id, $period, 'exam', 'Exam', $row, $maxScoreRow, $blockStart + self::OFFSET_EXAM, $now, $userId);
            }
        }

        $this->result->importedCount = count($this->matchedStudentIds);

        Grade::where('section_id', $this->sectionId)
            ->whereIn('period', array_keys(self::PERIOD_BLOCK_START))
            ->delete();

        foreach (array_chunk(array_values($toInsert), 200) as $chunk) {
            Grade::insert($chunk);
        }
    }

    private function queueItem(
        array &$toInsert,
        int $studentId,
        string $period,
        string $category,
        string $title,
        Collection $row,
        Collection $maxScoreRow,
        int $columnIndex,
        $now,
        ?int $userId
    ): void {
        $maxScore = $maxScoreRow[$columnIndex] ?? null;

        if (! is_numeric($maxScore) || (float) $maxScore <= 0) {
            return; // hindi ginagamit ang item na ito sa class na ito
        }

        $score = $row[$columnIndex] ?? 0;
        $score = is_numeric($score) ? (float) $score : 0;

        $key = $studentId . '|' . $category . '|' . $period . '|' . $title;

        $toInsert[$key] = [
            'student_id' => $studentId,
            'section_id' => $this->sectionId,
            'category' => $category,
            'period' => $period,
            'title' => $title,
            'score' => $score,
            'max_score' => (float) $maxScore,
            'recorded_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
