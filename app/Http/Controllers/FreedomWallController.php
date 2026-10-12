<?php

namespace App\Http\Controllers;

use App\Models\FreedomWallLike;
use App\Models\FreedomWallPost;
use App\Models\StudentActivity;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FreedomWallController extends Controller
{
    public const COLORS = ['yellow', 'pink', 'blue', 'green', 'orange', 'purple'];
    public const MAX_LENGTH = 280;

    // GET: latest notes of a section (query: section_id). Notes are anonymous, no author is exposed.
    public function index(Request $request): JsonResponse
    {
        $posts = FreedomWallPost::query()
            ->whereNull('hidden_at')
            ->withCount('likes')
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->latest('created_at')->latest('id')
            ->take(40)
            ->get();

        $liked = $request->filled('client_id')
            ? FreedomWallLike::where('client_id', (string) $request->query('client_id'))
                ->whereIn('post_id', $posts->pluck('id'))->pluck('post_id')->all()
            : [];

        $posts = $posts->map(fn ($p) => $this->present($p, in_array($p->id, $liked, true)));

        return response()->json(['posts' => $posts]);
    }

    // POST: student_number + password + body + color. The note goes to the sender's own section.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string'],
            'body' => ['required', 'string', 'max:' . self::MAX_LENGTH],
            'color' => ['nullable', 'string', Rule::in(self::COLORS)],
        ]);

        $student = $this->verify($data['student_number'], $data['password']);

        if (\App\Support\ProfanityFilter::contains($data['body'])) {
            throw ValidationException::withMessages([
                'body' => 'Bawal ang masasamang salita sa Freedom Wall. Pakipalitan ang note mo.',
            ]);
        }

        $recent = FreedomWallPost::where('student_id', $student->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recent >= 3) {
            throw ValidationException::withMessages([
                'body' => 'You can post up to 3 notes every 10 minutes.',
            ]);
        }

        $post = FreedomWallPost::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'body' => trim($data['body']),
            'color' => $data['color'] ?? 'yellow',
            'author_first_name' => $this->firstName($student->full_name),
        ]);

        StudentActivity::record(
            'wall_post',
            $student,
            "{$student->full_name} posted on the Freedom Wall: " . Str::limit(trim($data['body']), 120),
            ['post_id' => $post->id]
        );

        return response()->json([
            'message' => 'Posted on the wall.',
            'post' => $this->present($post->fresh()),
        ], 201);
    }

    // POST: toggle a like for a device (client_id). No sign-in, one like per device per note.
    public function like(Request $request, FreedomWallPost $freedomWallPost): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'string', 'min:8', 'max:64'],
        ]);

        $existing = FreedomWallLike::where('post_id', $freedomWallPost->id)
            ->where('client_id', $data['client_id'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            FreedomWallLike::create(['post_id' => $freedomWallPost->id, 'client_id' => $data['client_id']]);
        }

        return response()->json([
            'liked' => ! $existing,
            'likes_count' => $freedomWallPost->likes()->count(),
        ]);
    }

    // DELETE (admin only, route is inside the auth group): remove a note and its likes
    public function destroy(FreedomWallPost $freedomWallPost): JsonResponse
    {
        $freedomWallPost->delete();

        return response()->json(['deleted' => true]);
    }

    private function present(FreedomWallPost $p, bool $liked = false): array
    {
        return [
            'id' => $p->id,
            'body' => $p->body,
            'color' => $p->color,
            'section_id' => $p->section_id,
            'created_at' => $p->created_at,
            'likes_count' => $p->likes_count ?? $p->likes()->count(),
            'liked' => $liked,
            'author' => $p->author_first_name,
        ];
    }

    // Names are stored as "Last, First Middle": keep only the first given name
    private function firstName(?string $fullName): ?string
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return null;
        }

        $given = str_contains($fullName, ',') ? trim(explode(',', $fullName, 2)[1]) : $fullName;
        $first = explode(' ', $given)[0];

        return $first !== '' ? mb_substr($first, 0, 40) : null;
    }

    private function verify(string $studentNumber, string $password): Student
    {
        $student = Student::where('student_number', $studentNumber)->first();

        if (! $student || ! hash_equals((string) $student->password, $password)) {
            throw ValidationException::withMessages([
                'student_number' => 'Invalid student number or password.',
            ]);
        }

        if (is_null($student->password_changed_at)) {
            throw ValidationException::withMessages([
                'student_number' => 'Change your password first.',
            ]);
        }

        return $student;
    }
}
