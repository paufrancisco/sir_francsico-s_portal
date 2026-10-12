<?php

namespace App\Http\Controllers;

use App\Models\FreedomWallLike;
use App\Models\FreedomWallPost;
use App\Models\FreedomWallReply;
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
    public const MAX_REPLY_LENGTH = 200;
    public const TEACHER_NAME = 'Sir Francisco';

    // GET: latest notes of a section (query: section_id). Notes are anonymous, no author is exposed.
    public function index(Request $request): JsonResponse
    {
        $posts = FreedomWallPost::query()
            ->whereNull('hidden_at')
            ->withCount(['likes', 'replies'])
            ->with(['likes' => fn ($q) => $q->whereNotNull('author_first_name')->orderBy('id')])
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->latest('created_at')->latest('id')
            ->take(40)
            ->get();

        $likeKeys = array_values(array_filter([
            $request->filled('client_id') ? (string) $request->query('client_id') : null,
            auth()->check() ? 'teacher' : null,
        ]));

        $liked = $likeKeys
            ? FreedomWallLike::whereIn('client_id', $likeKeys)
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

    // POST: sign in (student_number + password) to like or unlike a note. liked=true likes, liked=false unlikes.
    public function like(Request $request, FreedomWallPost $freedomWallPost): JsonResponse
    {
        $teacher = $this->isTeacher($request);

        $data = $request->validate([
            'student_number' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'password' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'liked' => ['required', 'boolean'],
            'client_id' => ['nullable', 'string', 'max:64'],
        ]);

        $student = $teacher ? null : $this->verify($data['student_number'], $data['password']);
        $key = $teacher ? 'teacher' : 's' . $student->id;
        $device = $teacher ? null : ($data['client_id'] ?? null);
        $want = $request->boolean('liked');

        if ($want) {
            // An old anonymous like from this device becomes a named like
            if ($device) {
                FreedomWallLike::where('post_id', $freedomWallPost->id)->where('client_id', $device)->delete();
            }
            FreedomWallLike::firstOrCreate(
                ['post_id' => $freedomWallPost->id, 'client_id' => $key],
                $teacher
                    ? ['student_id' => null, 'author_first_name' => self::TEACHER_NAME]
                    : ['student_id' => $student->id, 'author_first_name' => $this->firstName($student->full_name)]
            );
        } else {
            FreedomWallLike::where('post_id', $freedomWallPost->id)
                ->where(function ($q) use ($key, $device) {
                    $q->where('client_id', $key);
                    if ($device) {
                        $q->orWhere('client_id', $device);
                    }
                })->delete();
        }

        return response()->json([
            'liked' => $want,
            'likes_count' => $freedomWallPost->likes()->count(),
            'likers' => $freedomWallPost->likes()->whereNotNull('author_first_name')
                ->orderBy('id')->limit(30)->pluck('author_first_name')->all(),
        ]);
    }

    // GET: replies of one note, oldest first (loaded only when the thread is opened)
    public function replies(FreedomWallPost $freedomWallPost): JsonResponse
    {
        abort_if($freedomWallPost->hidden_at !== null, 404);

        $replies = $freedomWallPost->replies()->orderBy('id')->take(100)->get()
            ->map(fn ($r) => $this->presentReply($r));

        return response()->json(['replies' => $replies]);
    }

    // POST: student_number + password + body. The reply shows the sender's first name.
    public function reply(Request $request, FreedomWallPost $freedomWallPost): JsonResponse
    {
        abort_if($freedomWallPost->hidden_at !== null, 404);

        $teacher = $this->isTeacher($request);

        $data = $request->validate([
            'student_number' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'password' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'body' => ['required', 'string', 'max:' . self::MAX_REPLY_LENGTH],
        ]);

        if ($teacher) {
            $reply = FreedomWallReply::create([
                'post_id' => $freedomWallPost->id,
                'student_id' => null,
                'author_first_name' => self::TEACHER_NAME,
                'body' => trim($data['body']),
            ]);

            return response()->json([
                'reply' => $this->presentReply($reply),
                'replies_count' => $freedomWallPost->replies()->count(),
            ], 201);
        }

        $student = $this->verify($data['student_number'], $data['password']);

        if (\App\Support\ProfanityFilter::contains($data['body'])) {
            throw ValidationException::withMessages([
                'body' => 'Bawal ang masasamang salita sa Freedom Wall. Pakipalitan ang reply mo.',
            ]);
        }

        $recent = FreedomWallReply::where('student_id', $student->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recent >= 10) {
            throw ValidationException::withMessages([
                'body' => 'You can send up to 10 replies every 10 minutes.',
            ]);
        }

        $reply = FreedomWallReply::create([
            'post_id' => $freedomWallPost->id,
            'student_id' => $student->id,
            'author_first_name' => $this->firstName($student->full_name),
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'reply' => $this->presentReply($reply),
            'replies_count' => $freedomWallPost->replies()->count(),
        ], 201);
    }

    // GET: tells the portal whether an admin session is active (likes and replies then go out as the teacher)
    public function me(): JsonResponse
    {
        return response()->json(['teacher' => auth()->check(), 'name' => self::TEACHER_NAME]);
    }

    // An admin session without student credentials in the request acts as the teacher
    private function isTeacher(Request $request): bool
    {
        return auth()->check() && ! $request->filled('student_number');
    }

    private function presentReply(FreedomWallReply $r): array
    {
        return [
            'id' => $r->id,
            'body' => $r->body,
            'author' => $r->author_first_name,
            'created_at' => $r->created_at,
        ];
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
            'likers' => $p->relationLoaded('likes') ? $p->likes->pluck('author_first_name')->take(30)->values()->all() : [],
            'replies_count' => $p->replies_count ?? $p->replies()->count(),
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
