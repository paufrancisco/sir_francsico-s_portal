<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
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
                    // FIX: dating asset('storage/' . $seat->student->photo_path) — nagbubuo ito ng
                    // LOCAL Laravel storage URL (127.0.0.1:8000/storage/...) na hindi valid dahil
                    // Supabase na ang totoong pinag-uupload-an ng photos. Ang $student->photo_url
                    // accessor sa Model ang gumagawa ng tamang Supabase URL.
                    'photo_url' => $seat->student->photo_url,
                ] : null,
            ]]),
            'unassignedStudents' => $unassignedStudents->map(fn ($s) => [
                'id' => $s->id,
                'student_number' => $s->student_number,
                'full_name' => $s->full_name,
                'aura_points' => $s->aura_points,
                // FIX: parehong dahilan — gamitin ang accessor imbes na manual na asset() build.
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

        // Alisin muna kung may existing seat na ang estudyante sa parehong layout+section
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

    public function adjustAura(Request $request, Student $student)
    {
        $request->validate(['delta' => 'required|integer']);

        $newValue = max(0, $student->aura_points + $request->delta);
        $student->update(['aura_points' => $newValue]);

        return response()->json(['aura_points' => $newValue]);
    }
}