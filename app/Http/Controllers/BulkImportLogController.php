<?php

namespace App\Http\Controllers;

use App\Models\BulkImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkImportLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Reminder: first_name rule must match nameValid in BulkImportTab.vue
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:40', "regex:/^[\\pL][\\pL .'-]*$/u"],
            'period' => ['required', 'in:Prelim,Midterm,Pre-Final,Finals'],
            'student_count' => ['required', 'integer', 'min:1', 'max:500'],
            'dry_run' => ['required', 'boolean'],
        ]);

        $data['first_name'] = trim($data['first_name']);
        $data['ip_address'] = $request->ip();

        BulkImportLog::create($data);

        return response()->json(['ok' => true]);
    }
}
