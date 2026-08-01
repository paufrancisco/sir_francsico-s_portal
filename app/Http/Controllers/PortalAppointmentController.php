<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\FacultyAvailability;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PortalAppointmentController extends Controller
{
    /**
     * NOTE: this mirrors whatever credential-check helper your grades/chat
     * controllers already use. If you already have a shared method (e.g.
     * PortalController::verifyStudent()), swap this out for that instead
     * of duplicating the logic here.
     */
    private function resolveStudent(Request $request): Student
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::where('student_number', $data['student_number'])->first();

        if (! $student || $student->password !== $data['password']) {
            abort(422, 'Invalid student number or password.');
        }

        return $student;
    }

    /**
     * POST /portal/appointments/verify
     * Sign-in step, same shape as grades/chat verify.
     */
    public function verify(Request $request)
    {
        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        return response()->json([
            'student_id' => $student->id,
            'name' => $student->name,
        ]);
    }

    /**
     * POST /portal/appointments/available
     * Returns open slots plus the student's current pending/approved appointment, if any.
     */
    public function available(Request $request)
    {
        $student = $this->resolveStudent($request);

        $slots = FacultyAvailability::open()
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'date' => $slot->date->toDateString(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ]);

        $existing = Appointment::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();

        return response()->json([
            'slots' => $slots,
            'existing_appointment' => $existing ? [
                'id' => $existing->id,
                'appointment_date' => $existing->appointment_date->toDateString(),
                'start_time' => $existing->start_time,
                'end_time' => $existing->end_time,
                'reason' => $existing->reason,
                'status' => $existing->status,
            ] : null,
        ]);
    }

    /**
     * POST /portal/appointments/book
     */
    public function book(Request $request)
    {
        $student = $this->resolveStudent($request);

        $data = $request->validate([
            'faculty_availability_id' => ['required', 'integer', Rule::exists('faculty_availabilities', 'id')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyHasActive = Appointment::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($alreadyHasActive) {
            abort(422, 'You already have an active appointment. Cancel it first before booking another.');
        }

        $appointment = null;

        \DB::transaction(function () use ($data, $student, &$appointment) {
            $slot = FacultyAvailability::lockForUpdate()->findOrFail($data['faculty_availability_id']);

            if (! $slot->is_active || $slot->is_booked) {
                abort(422, 'Sorry, that slot is no longer available. Please pick another.');
            }

            $appointment = Appointment::create([
                'student_id' => $student->id,
                'faculty_availability_id' => $slot->id,
                'appointment_date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);

            $slot->update(['is_booked' => true]);
        });

        return response()->json([
            'message' => 'Appointment request sent.',
            'appointment' => [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'reason' => $appointment->reason,
                'status' => $appointment->status,
            ],
        ]);
    }

    /**
     * DELETE /portal/appointments/{appointment}
     */
    public function destroy(Request $request, Appointment $appointment)
    {
        $student = $this->resolveStudent($request);

        if ($appointment->student_id !== $student->id) {
            abort(403, 'This appointment does not belong to you.');
        }

        if ($appointment->status !== 'pending') {
            abort(422, 'Only pending appointments can be cancelled.');
        }

        \DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'cancelled']);
            $appointment->availability?->update(['is_booked' => false]);
        });

        return response()->json(['message' => 'Appointment cancelled.']);
    }
}