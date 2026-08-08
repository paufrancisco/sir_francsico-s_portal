<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GradesImport implements WithMultipleSheets
{
    public array $skipped = [];
    public array $duplicates = [];
    public int $importedCount = 0;

    public function __construct(protected int $sectionId)
    {
    }

    public function sheets(): array
    {
        // Yung "Input" sheet lang ng STI Class Record (P60) template ang
        // babasahin natin - dito nakatago ang raw scores. Yung ibang sheets
        // (PRELIMS, MIDTERM, PRE-FINALS, FINALS, Summary, atbp.) ay pawang
        // computed/printable views na lang, kaya hindi na natin kailangang
        // pakielaman.
        return [
            'Input' => new GradesInputSheetImport($this->sectionId, $this),
        ];
    }
}
