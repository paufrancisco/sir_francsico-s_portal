<?php

namespace App\Http\Controllers;

use App\Models\AuraPointLog;
use App\Models\Seat;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SeatingController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::orderBy('name')->get(['id', 'name']);

        $sectionId = $request->input('section_id', $sections->first()?->id);
        $layout = $request->input('layout', 'lecture');

        $students = Student::where('section_id', $sectionId)
            ->orderBy('full_name')
            ->get(['id', 'student_number', 'full_name', 'aura_points', 'photo_path']);

        $seats = Seat::where('section_id', $sectionId)
            ->where('layout', $layout)
            ->with('student:id,student_number,full_name,aura_points,photo_path')
            ->get()
            ->keyBy('position_x');

        $assignedStudentIds = $seats->pluck('student_id')->filter()->values();

        $unassignedStudents = $students->whereNotIn('id', $assignedStudentIds)->values();

        return Inertia::render('Admin/Seating/Index', [
            'sections' => $sections,
            'activeSectionId' => (int) $sectionId,
            'layout' => $layout,
            'seats' => $seats->mapWithKeys(fn ($seat, $pos) => [$pos => [
                'student_id' => $seat->student_id,
                'student' => $seat->student ? [
                    'id' => $seat->student->id,
                    'student_number' => $seat->student->student_number,
                    'full_name' => $seat->student->full_name,
                    'aura_points' => $seat->student->aura_points,
                    'photo_url' => $seat->student->photo_url,
                ] : null,
            ]]),
            'unassignedStudents' => $unassignedStudents->map(fn ($s) => [
                'id' => $s->id,
                'student_number' => $s->student_number,
                'full_name' => $s->full_name,
                'aura_points' => $s->aura_points,
                'photo_url' => $s->photo_url,
            ]),
        ]);
    }

    public function assign(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'layout' => 'required|in:lecture,comlab',
            'position' => 'required|integer|min:0',
            'student_id' => 'required|exists:students,id',
        ]);

        Seat::where('section_id', $request->section_id)
            ->where('layout', $request->layout)
            ->where('student_id', $request->student_id)
            ->delete();

        Seat::updateOrCreate(
            [
                'section_id' => $request->section_id,
                'layout' => $request->layout,
                'position_x' => $request->position,
            ],
            [
                'student_id' => $request->student_id,
                'seat_label' => 'Seat ' . ($request->position + 1),
                'position_y' => 0,
            ]
        );

        return back();
    }

    public function unassign(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'layout' => 'required|in:lecture,comlab',
            'position' => 'required|integer|min:0',
        ]);

        Seat::where('section_id', $request->section_id)
            ->where('layout', $request->layout)
            ->where('position_x', $request->position)
            ->delete();

        return back();
    }

    // ---- Single-student aura adjustment (existing, now also logs) ----
    public function adjustAura(Request $request, Student $student)
    {
        $request->validate(['delta' => 'required|integer']);

        $newValue = max(0, $student->aura_points + $request->delta);
        $actualDelta = $newValue - $student->aura_points;

        $student->update(['aura_points' => $newValue]);

        if ($actualDelta !== 0) {
            AuraPointLog::create([
                'student_id' => $student->id,
                'section_id' => $student->section_id,
                'points' => $actualDelta,
                'recorded_by' => Auth::id(),
            ]);
        }

        return response()->json(['aura_points' => $newValue]);
    }

    // ---- Bulk aura adjustment (Select All + Quick +5/+1) ----
    public function bulkAdjustAura(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'delta' => 'required|integer',
        ]);

        $students = Student::whereIn('id', $request->student_ids)->get();
        $updated = [];

        DB::transaction(function () use ($students, $request, &$updated) {
            foreach ($students as $student) {
                $newValue = max(0, $student->aura_points + $request->delta);
                $actualDelta = $newValue - $student->aura_points;

                $student->update(['aura_points' => $newValue]);

                if ($actualDelta !== 0) {
                    AuraPointLog::create([
                        'student_id' => $student->id,
                        'section_id' => $student->section_id,
                        'points' => $actualDelta,
                        'recorded_by' => Auth::id(),
                    ]);
                }

                $updated[$student->id] = $newValue;
            }
        });

        return response()->json(['updated' => $updated]);
    }

    // ---- Lec Lab Summary tab: student x date pivot ----
    public function auraSummary(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $students = Student::where('section_id', $request->section_id)
            ->orderBy('full_name')
            ->get(['id', 'student_number', 'full_name', 'aura_points']);

        $logs = AuraPointLog::where('section_id', $request->section_id)
            ->orderBy('created_at')
            ->get(['student_id', 'points', 'note', 'created_at']);

        // Distinct dates (Y-m-d) na may activity, pinagsunod-sunod
        $dates = $logs->map(fn ($l) => $l->created_at->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        $rows = $students->map(function ($student) use ($logs, $dates) {
            $studentLogs = $logs->where('student_id', $student->id);

            $byDate = $dates->mapWithKeys(function ($date) use ($studentLogs) {
                $sum = $studentLogs->filter(fn ($l) => $l->created_at->format('Y-m-d') === $date)
                    ->sum('points');
                return [$date => $sum === 0 && $studentLogs->where(fn ($l) => $l->created_at->format('Y-m-d') === $date)->isEmpty() ? null : $sum];
            });

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_number' => $student->student_number,
                'by_date' => $byDate,
                'total' => $student->aura_points,
            ];
        });

        return response()->json([
            'dates' => $dates,
            'rows' => $rows,
        ]);
    }

    // ---- Reset all aura points sa isang section ----
    public function resetAura(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);

        $students = Student::where('section_id', $request->section_id)
            ->where('aura_points', '>', 0)
            ->get();

        DB::transaction(function () use ($students) {
            foreach ($students as $student) {
                // Offsetting log entry — para tumugma pa rin ang sum ng logs sa
                // bagong total (0) kahit hindi natin binubura ang history.
                AuraPointLog::create([
                    'student_id' => $student->id,
                    'section_id' => $student->section_id,
                    'points' => -$student->aura_points,
                    'note' => 'Reset',
                    'recorded_by' => Auth::id(),
                ]);

                $student->update(['aura_points' => 0]);
            }
        });

        return back();
    }
}