<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\FacultyAvailability;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFacultyAvailabilityController extends Controller
{
    // GET /paulo/availability  ->  Appointments page (slots + student requests)
    public function index()
    {
        $slots = FacultyAvailability::orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        $requests = Appointment::with('student:id,full_name,student_number')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'student_name' => $a->student->full_name ?? '—',
                'student_number' => $a->student->student_number ?? '—',
                'appointment_date' => $a->appointment_date->toDateString(),
                'start_time' => $a->start_time,
                'end_time' => $a->end_time,
                'reason' => $a->reason,
                'status' => $a->status,
                'admin_notes' => $a->admin_notes,
                'created_at' => $a->created_at,
            ]);

        return Inertia::render('Admin/Appointments/Index', [
            'slots' => $slots,
            'requests' => $requests,
        ]);
    }

    // POST /paulo/availability
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        FacultyAvailability::create([
            ...$data,
            'is_active' => true,
            'is_booked' => false,
        ]);

        return back()->with('success', 'Available slot added.');
    }

    // PATCH /paulo/availability/{availability}
    public function update(Request $request, FacultyAvailability $availability)
    {
        if ($availability->is_booked) {
            return back()->withErrors(['availability' => 'This slot already has a booked appointment — resolve that appointment first.']);
        }

        $data = $request->validate([
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $availability->update($data);

        return back()->with('success', 'Slot updated.');
    }

    // DELETE /paulo/availability/{availability}
    public function destroy(FacultyAvailability $availability)
    {
        if ($availability->is_booked) {
            return back()->withErrors(['availability' => 'This slot already has a booked appointment — resolve that appointment first.']);
        }

        $availability->delete();

        return back()->with('success', 'Slot removed.');
    }

    // PATCH /paulo/appointments/{appointment}/resolve
    public function resolve(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,declined'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment->update($data);

        // Declined requests free up the slot again so other students can book it.
        if ($data['status'] === 'declined') {
            $appointment->availability?->update(['is_booked' => false]);
        }

        return back()->with('success', 'Appointment ' . $data['status'] . '.');
    }
}