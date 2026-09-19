<?php

namespace App\Imports;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class GradesInputSheetImport implements ToCollection, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    private const PERIOD_BLOCK_START = [
        'prelim' => 6,
        'midterm' => 19,
        'prefinal' => 32,
        'finals' => 45,
    ];

    private const OFFSET_PTL = [0, 1, 2, 3];
    private const OFFSET_QUIZ = [5, 6, 7, 8];
    private const OFFSET_EXAM = 10;

    private const MAX_SCORE_ROW_INDEX = 5;
    private const DATA_START_ROW_INDEX = 7;

    private const STUDENT_NUMBER_COL = 3; // Column D
    private const STUDENT_NAME_COL = 5;   // Column F

    private const STUDENT_NUMBER_OFFSET = 2000000000;

    protected array $seenStudentNumbers = [];
    protected array $matchedStudentIds = [];

    public function __construct(protected int $sectionId, protected GradesImport $result)
    {
    }

    public function collection(Collection $rows)
    {
        \Log::info('import START', ['rows' => $rows->count(), 'section' => $this->sectionId]);

        $maxScoreRow = $rows->get(self::MAX_SCORE_ROW_INDEX);

        \Log::info('import maxScoreRow', $maxScoreRow ? $maxScoreRow->toArray() : ['NULL']);

        if (! $maxScoreRow) {
            return;
        }

        $students = Student::where('section_id', $this->sectionId)
            ->get()
            ->keyBy(fn ($s) => $this->normalizeStudentNumber($s->student_number));

        \Log::info('import students loaded', ['count' => $students->count(), 'keys' => $students->keys()->take(5)->all()]);

        $now = now();
        $userId = auth()->id();
        $toInsert = [];

        $dataRows = $rows->slice(self::DATA_START_ROW_INDEX);

        // Alamin kung aling period ang may aktwal na score sa file.
        $activePeriods = $this->periodsWithScores($dataRows);

        \Log::info('import activePeriods', $activePeriods);
        \Log::info('import first row', $dataRows->first() ? $dataRows->first()->toArray() : []);

        if (empty($activePeriods)) {
            $this->result->importedCount = 0;
            return; // walang nabasang score, huwag galawin ang existing grades
        }

        foreach ($dataRows as $row) {
            $studentNumberRaw = trim((string) ($row[self::STUDENT_NUMBER_COL] ?? ''));

            if ($studentNumberRaw === '') {
                continue;
            }

            $lookupKey = $this->normalizeStudentNumber($studentNumberRaw);

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
                if (! in_array($period, $activePeriods, true)) {
                    continue; // walang score sa period na ito sa file
                }

                foreach (self::OFFSET_PTL as $i => $offset) {
                    $this->queueItem($toInsert, $student->id, $period, 'tp', 'PT/Lab ' . ($i + 1), $row, $maxScoreRow, $blockStart + $offset, $now, $userId);
                }

                foreach (self::OFFSET_QUIZ as $i => $offset) {
                    $this->queueItem($toInsert, $student->id, $period, 'long_quiz', 'Quiz ' . ($i + 1), $row, $maxScoreRow, $blockStart + $offset, $now, $userId);
                }

                $this->queueItem($toInsert, $student->id, $period, 'exam', 'Exam', $row, $maxScoreRow, $blockStart + self::OFFSET_EXAM, $now, $userId);
            }
        }

        \Log::info('import toInsert count', [
            'toInsert' => count($toInsert),
            'matched' => count($this->matchedStudentIds),
            'skipped' => $this->result->skipped,
        ]);

        if (empty($toInsert)) {
            $this->result->importedCount = 0;
            return; // huwag i-delete ang existing grades kung wala namang ipapalit
        }

        $this->result->importedCount = count($this->matchedStudentIds);

        // Yung mga period lang na nasa file ang buburahin at papalitan.
        Grade::where('section_id', $this->sectionId)
            ->whereIn('period', $activePeriods)
            ->delete();

        foreach (array_chunk(array_values($toInsert), 200) as $chunk) {
            Grade::insert($chunk);
        }
    }

    /**
     * Ibabalik ang mga period na may kahit isang numeric na score
     * sa mga row ng estudyante. Kung may max score lang pero walang
     * score (hal. Pre-final Exam na 50 sa Settings), hindi ito active.
     */
    private function periodsWithScores(Collection $dataRows): array
    {
        $active = [];

        foreach (self::PERIOD_BLOCK_START as $period => $start) {
            $cols = [
                ...array_map(fn ($o) => $start + $o, self::OFFSET_PTL),
                ...array_map(fn ($o) => $start + $o, self::OFFSET_QUIZ),
                $start + self::OFFSET_EXAM,
            ];

            $hasScore = $dataRows->contains(
                fn ($row) => collect($cols)->contains(fn ($c) => is_numeric($row[$c] ?? null))
            );

            if ($hasScore) {
                $active[] = $period;
            }
        }

        return $active;
    }

    private function normalizeStudentNumber($value): string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return '';
        }

        if (ctype_digit($value)) {
            $num = (int) $value;

            if ($num >= self::STUDENT_NUMBER_OFFSET) {
                $num -= self::STUDENT_NUMBER_OFFSET;
            }

            return (string) $num;
        }

        return $value;
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
            return;
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