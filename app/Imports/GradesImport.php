<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
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
        return [
            'Input' => new GradesInputSheetImport($this->sectionId, $this),

            // Kailangan ito para may mabasa ang formulas ng Input mula sa Settings.
            'Settings' => new class implements ToCollection, HasReferencesToOtherSheets {
                public function collection(Collection $rows) {}
            },
        ];
    }
}