<?php

namespace App\Http\Controllers;

use App\Models\ClassmateMessage;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassmateMessageController extends Controller
{
    private const MAX_LENGTH = 300;
    private const DAILY_LIMIT = 3;
    private const LIST_LIMIT = 30;

    // Public: messages shown under a student's photo (hidden ones are skipped)
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['nullable', 'integer'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
        ]);

        $recipient = $this->findRecipient($data['recipient_id'] ?? null, $data['recipient_name'] ?? null);

        if (! $recipient) {
            return response()->json(['messages' => []]);
        }

        $messages = ClassmateMessage::with('sender:id,full_name')
            ->where('recipient_id', $recipient->id)
            ->whereNull('hidden_at')
            ->latest()
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (ClassmateMessage $m) => [
                'id' => $m->id,
                'sender_name' => $m->sender?->full_name ?? 'Classmate',
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    // Public, verified via student_number + password in the request body.
    // Reminder: recipient is ALWAYS the sender, so the post shows on their own card.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
            'body' => ['required', 'string', 'max:' . self::MAX_LENGTH],
        ]);

        $sender = $this->verifySender($data['student_number'], $data['password']);

        $postedToday = ClassmateMessage::where('sender_id', $sender->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($postedToday >= self::DAILY_LIMIT) {
            throw ValidationException::withMessages([
                'body' => 'You have reached today\'s posting limit.',
            ]);
        }

        ClassmateMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $sender->id,
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'message' => 'Posted.',
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->full_name,
            ],
        ], 201);
    }

    // PATCH: edit your own message (same login check as posting)
    public function update(Request $request, ClassmateMessage $classmateMessage): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
            'body' => ['required', 'string', 'max:' . self::MAX_LENGTH],
        ]);

        $sender = $this->verifySender($data['student_number'], $data['password']);
        $this->assertOwner($classmateMessage, $sender);

        $classmateMessage->update(['body' => trim($data['body'])]);

        return response()->json(['message' => 'Updated.']);
    }

    // DELETE: remove your own message (same login check as posting)
    public function destroy(Request $request, ClassmateMessage $classmateMessage): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $sender = $this->verifySender($data['student_number'], $data['password']);
        $this->assertOwner($classmateMessage, $sender);

        $classmateMessage->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function verifySender(string $studentNumber, string $password): Student
    {
        $sender = Student::where('student_number', $studentNumber)->first();

        if (! $sender || $sender->password !== $password) {
            throw ValidationException::withMessages([
                'student_number' => 'Invalid student number or password.',
            ]);
        }

        if (is_null($sender->password_changed_at)) {
            throw ValidationException::withMessages([
                'student_number' => 'Change your password first.',
            ]);
        }

        return $sender;
    }

    // Reminder: hidden (moderated) messages can no longer be edited or deleted by the sender
    private function assertOwner(ClassmateMessage $message, Student $student): void
    {
        if ((int) $message->sender_id !== (int) $student->id || ! is_null($message->hidden_at)) {
            throw ValidationException::withMessages([
                'student_number' => 'You can only change your own messages.',
            ]);
        }
    }

    private function findRecipient(?int $id, ?string $name): ?Student
    {
        if ($id) {
            return Student::find($id);
        }

        if (! $name) {
            return null;
        }

        return Student::where('full_name', $name)->first();
    }
}
