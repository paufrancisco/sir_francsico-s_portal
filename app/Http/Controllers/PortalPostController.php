<?php

namespace App\Http\Controllers;

use App\Models\PortalPost;
use App\Models\PortalPostReaction;
use App\Models\Student;
use App\Support\ProfanityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortalPostController extends Controller
{
    public const REACTIONS = ['heart', 'like', 'haha', 'wow', 'sad', 'angry'];
    public const LAYOUTS = ['auto', 'grid', 'feature-left', 'feature-top', 'rows'];
    public const MAX_CAPTION = 500;
    public const MAX_IMAGES = 6;
    public const TEACHER_NAME = 'Sir Francisco';
    private const PER_PAGE = 10;

    // GET: newest posts of a section, cursor paging (query: section_id, before, client_id)
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => ['nullable', 'integer'],
            'before' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'string', 'max:64'],
        ]);

        $rows = PortalPost::query()
            ->with('images')
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->filled('before'), fn ($q) => $q->where('id', '<', $request->integer('before')))
            ->orderByDesc('id')
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::PER_PAGE;
        $posts = $rows->take(self::PER_PAGE)->values();
        $ids = $posts->pluck('id');

        $counts = [];
        PortalPostReaction::whereIn('post_id', $ids)
            ->selectRaw('post_id, reaction, count(*) as total')
            ->groupBy('post_id', 'reaction')
            ->get()
            ->each(function ($r) use (&$counts) {
                $counts[$r->post_id][$r->reaction] = (int) $r->total;
            });

        $mine = PortalPostReaction::whereIn('post_id', $ids)
            ->when(
                auth()->check(),
                fn ($q) => $q->where('actor', 'teacher'),
                fn ($q) => $q->where('device_id', (string) $request->query('client_id', ''))
            )
            ->pluck('reaction', 'post_id')
            ->all();

        $names = [];
        PortalPostReaction::whereIn('post_id', $ids)
            ->whereNotNull('author_first_name')
            ->orderBy('id')
            ->get(['post_id', 'author_first_name'])
            ->each(function ($r) use (&$names) {
                if (count($names[$r->post_id] ?? []) < 30) {
                    $names[$r->post_id][] = $r->author_first_name;
                }
            });

        return response()->json([
            'posts' => $posts->map(fn ($p) => $this->present(
                $p,
                $counts[$p->id] ?? [],
                $mine[$p->id] ?? null,
                $names[$p->id] ?? []
            ))->values(),
            'has_more' => $hasMore,
            'next_before' => $posts->last()?->id,
        ]);
    }

    // POST (multipart): student_number + password + caption + layout + images[]. The post goes to the sender's section.
    public function store(Request $request): JsonResponse
    {
        $teacher = $this->isTeacher($request);

        $data = $request->validate([
            'student_number' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'password' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'caption' => ['nullable', 'string', 'max:' . self::MAX_CAPTION],
            'layout' => ['nullable', 'string', Rule::in(self::LAYOUTS)],
            'section_id' => $teacher ? ['required', 'integer', 'exists:sections,id'] : ['nullable'],
            'images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $student = $teacher ? null : $this->verify($data['student_number'], $data['password']);

        $caption = trim((string) ($data['caption'] ?? ''));
        $files = $request->file('images', []);

        if ($caption === '' && count($files) === 0) {
            throw ValidationException::withMessages(['caption' => 'Write something or add a photo first.']);
        }

        if ($caption !== '' && ProfanityFilter::contains($caption)) {
            throw ValidationException::withMessages([
                'caption' => 'Bawal ang masasamang salita. Pakipalitan ang caption mo.',
            ]);
        }

        if ($student) {
            $recent = PortalPost::where('student_id', $student->id)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recent >= 5) {
                throw ValidationException::withMessages([
                    'caption' => 'You can post up to 5 times every 10 minutes.',
                ]);
            }
        }

        $paths = [];

        try {
            foreach ($files as $file) {
                $paths[] = $file->store('portal-posts', 'supabase');
            }

            $post = DB::transaction(function () use ($data, $student, $teacher, $caption, $paths) {
                $post = PortalPost::create([
                    'student_id' => $student?->id,
                    'section_id' => $teacher ? (int) $data['section_id'] : $student->section_id,
                    'caption' => $caption !== '' ? $caption : null,
                    'layout' => count($paths) > 1 ? ($data['layout'] ?? 'auto') : 'auto',
                    'author_first_name' => $teacher ? self::TEACHER_NAME : $this->firstName($student->full_name),
                ]);

                foreach ($paths as $i => $path) {
                    $post->images()->create(['path' => $path, 'position' => $i]);
                }

                return $post;
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('supabase')->delete($path);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Posted.',
            'post' => $this->present($post->load('images'), [], null, []),
        ], 201);
    }

    // POST: set, change, or remove (reaction = null) my reaction on a post
    public function react(Request $request, PortalPost $portalPost): JsonResponse
    {
        $teacher = $this->isTeacher($request);

        $data = $request->validate([
            'student_number' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'password' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'reaction' => ['nullable', 'string', Rule::in(self::REACTIONS)],
            'client_id' => ['nullable', 'string', 'max:64'],
        ]);

        $student = $teacher ? null : $this->verify($data['student_number'], $data['password']);
        $actor = $teacher ? 'teacher' : 's' . $student->id;

        if (empty($data['reaction'])) {
            PortalPostReaction::where('post_id', $portalPost->id)->where('actor', $actor)->delete();
        } else {
            PortalPostReaction::updateOrCreate(
                ['post_id' => $portalPost->id, 'actor' => $actor],
                [
                    'student_id' => $student?->id,
                    'author_first_name' => $teacher ? self::TEACHER_NAME : $this->firstName($student->full_name),
                    'reaction' => $data['reaction'],
                    'device_id' => $data['client_id'] ?? null,
                ]
            );
        }

        $counts = PortalPostReaction::where('post_id', $portalPost->id)
            ->selectRaw('reaction, count(*) as total')
            ->groupBy('reaction')
            ->pluck('total', 'reaction')
            ->map(fn ($n) => (int) $n)
            ->all();

        return response()->json([
            'liked' => ! empty($data['reaction']),
            'my_reaction' => $data['reaction'] ?: null,
            'reaction_counts' => $counts,
            'likes_count' => array_sum($counts),
            'likers' => PortalPostReaction::where('post_id', $portalPost->id)
                ->whereNotNull('author_first_name')
                ->orderBy('id')->limit(30)->pluck('author_first_name')->all(),
        ]);
    }

    // DELETE (admin only, route is inside the auth group): remove a post and its image files
    public function destroy(PortalPost $portalPost): JsonResponse
    {
        foreach ($portalPost->images as $image) {
            Storage::disk('supabase')->delete($image->path);
        }

        $portalPost->delete();

        return response()->json(['deleted' => true]);
    }

    // PATCH: edit a post's caption. Only the student who wrote it (verified by
    // student number + password) or the teacher session can edit. Images stay as is.
    public function update(Request $request, PortalPost $portalPost): JsonResponse
    {
        $teacher = $this->isTeacher($request);

        $data = $request->validate([
            'student_number' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'password' => $teacher ? ['nullable', 'string'] : ['required', 'string'],
            'caption' => ['nullable', 'string', 'max:' . self::MAX_CAPTION],
        ]);

        if (! $teacher) {
            $student = $this->verify($data['student_number'], $data['password']);

            if ($portalPost->student_id === null || (int) $portalPost->student_id !== (int) $student->id) {
                abort(403, 'You can only edit your own posts.');
            }
        }

        $caption = trim((string) ($data['caption'] ?? ''));

        if ($caption === '' && $portalPost->images()->count() === 0) {
            throw ValidationException::withMessages(['caption' => 'A post with no photo needs a caption.']);
        }

        if ($caption !== '' && ProfanityFilter::contains($caption)) {
            throw ValidationException::withMessages([
                'caption' => 'Bawal ang masasamang salita. Pakipalitan ang caption mo.',
            ]);
        }

        $portalPost->update(['caption' => $caption !== '' ? $caption : null]);

        return response()->json(['caption' => $portalPost->caption]);
    }

    private function present(PortalPost $p, array $counts, ?string $mine, array $likers): array
    {
        return [
            'id' => $p->id,
            'caption' => $p->caption,
            'layout' => $p->layout,
            'section_id' => $p->section_id,
            'author' => $p->author_first_name,
            'is_teacher' => $p->student_id === null,
            'created_at' => $p->created_at,
            'images' => $p->images->map(fn ($i) => [
                'id' => $i->id,
                'url' => Storage::disk('supabase')->temporaryUrl($i->path, now()->addHours(6)),
            ])->values()->all(),
            'reaction_counts' => $counts,
            'likes_count' => array_sum($counts),
            'my_reaction' => $mine,
            'liked' => $mine !== null,
            'likers' => $likers,
        ];
    }

    // An admin session without student credentials in the request acts as the teacher
    private function isTeacher(Request $request): bool
    {
        return auth()->check() && ! $request->filled('student_number');
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
