<?php

namespace App\Http\Controllers;

use App\Mail\NewChatMessageMail;
use App\Models\ChatSession;
use App\Models\Faq;
use App\Models\Message;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ChatController extends Controller
{
    private const MAX_CONCURRENT_CHATTERS = 3;
    private const SESSION_WINDOW_MINUTES = 15;

    // Sinusubukan sa pagkakasunod-sunod na ito — kapag na-fail o walang laman
    // ang response ng isa, susubukan ang susunod bago mag-give up.
    private const GEMINI_MODEL_FALLBACK_CHAIN = [
        'gemini-3.6-flash',
        'gemini-3.5-flash-lite',
        'gemini-2.5-flash',
        'gemini-flash-latest',
    ];

    public function verify(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Incorrect student number or password.'], 422);
        }

        if (is_null($student->password_changed_at)) {
            return response()->json(['must_change_password' => true]);
        }

        $cutoff = now()->subMinutes(self::SESSION_WINDOW_MINUTES);
        $activeSessions = ChatSession::where('last_active_at', '>=', $cutoff)->get();
        $alreadyActive = $activeSessions->firstWhere('student_id', $student->id);

        if (! $alreadyActive && $activeSessions->count() >= self::MAX_CONCURRENT_CHATTERS) {
            return response()->json([
                'message' => 'The AI assistant is busy right now and is already chatting with 3 students. Please try again in a few minutes. In the meantime, the answer might already be in the FAQ or Announcements!',
            ], 429);
        }

        ChatSession::updateOrCreate(
            ['student_id' => $student->id],
            ['last_active_at' => now()]
        );

        return response()->json([
            'student_id' => $student->id,
            'name' => $student->full_name,
        ]);
    }

    public function history(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);

        $messages = Message::where('student_id', $request->student_id)
            ->orderBy('created_at')
            ->get(['id', 'sender', 'body', 'created_at']);

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'body' => 'required|string|max:1000',
        ]);

        $student = Student::findOrFail($request->student_id);

        // i-refresh yung active slot niya
        ChatSession::updateOrCreate(
            ['student_id' => $student->id],
            ['last_active_at' => now()]
        );

        $studentMessage = Message::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'sender' => 'student',
            'body' => $request->body,
        ]);

        [$replyText, $needsReview] = $this->askGemini($student, $request->body);

        $aiMessage = Message::create([
            'student_id' => $student->id,
            'section_id' => $student->section_id,
            'sender' => 'ai',
            'body' => $replyText,
            'needs_review' => $needsReview,
        ]);

        // Mag-e-email kay Sir Francisco sa BAWAT bagong tanong ng estudyante,
        // nasagot man nang maayos ng AI o hindi.
        if (config('services.admin_notify_email')) {
            $transcript = Message::where('student_id', $student->id)
                ->orderBy('created_at')
                ->get(['sender', 'body', 'created_at']);

            // Malinaw na context para agad makilala ni Sir Francisco kung sino
            // at anong klase ang nagtatanong, kahit hindi niya buksan ang portal.
            $studentContext = [
                'name' => $student->full_name,
                'student_number' => $student->student_number,
                'subject' => optional($student->section)->subject ?? '—',
                'section' => optional($student->section)->name ?? '—',
            ];

            \Log::info('Admin notify email: attempting send', [
                'to' => config('services.admin_notify_email'),
                'student' => $student->full_name,
            ]);

            try {
                Mail::to(config('services.admin_notify_email'))
                    ->send(new NewChatMessageMail($student, $transcript, $needsReview, $studentContext));

                \Log::info('Admin notify email: sent successfully');
            } catch (\Throwable $e) {
                // Huwag hayaang mag-fail ang buong chat response kapag nag-error
                // lang ang pag-send ng notification email — log na lang, itutuloy
                // pa rin ang response papunta sa student.
                \Log::error('Admin notify email failed', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json([
            'messages' => [$studentMessage, $aiMessage],
        ]);
    }

    private function askGemini(Student $student, string $question): array
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return ['Sorry, the AI assistant is not configured yet. Sir Francisco will look at your question instead.', true];
        }

        $siblingSectionIds = $student->section
            ? \App\Models\Section::where('name', $student->section->name)->pluck('id')
            : collect();

        $faqs = Faq::whereNull('section_id')
            ->orWhereIn('section_id', $siblingSectionIds)
            ->with('section')
            ->get(['id', 'question', 'answer', 'section_id']);

        $faqText = $faqs->isEmpty()
            ? '(no FAQs set yet)'
            : $faqs->map(function ($f) {
                $label = $f->section ? "[{$f->section->subject}] " : '';
                return "{$label}Q: {$f->question}\nA: {$f->answer}";
            })->implode("\n\n");

        $recent = Message::where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->reverse();

        $historyText = $recent->map(function ($m) {
            $label = $m->sender === 'student' ? 'Student' : ($m->sender === 'admin' ? 'Sir Francisco' : 'AI');
            return "{$label}: {$m->body}";
        })->implode("\n");

        $studentFirstName = trim(explode(',', $student->full_name)[1] ?? $student->full_name);

        $prompt = <<<PROMPT
        You are "AI Assistant", the chat assistant of Sir Francisco in his class portal. You are talking to {$studentFirstName}. Always reply in English.

        FAQ list (base your answers only on this information about the class, quizzes, deadlines, etc.):
        {$faqText}

        Conversation history:
        {$historyText}

        {$studentFirstName}: {$question}

        How you should behave:
        - You are talking to a person, not a FAQ lookup machine - chat naturally like a real AI assistant, using their name occasionally.
        - If it is only a greeting (hi, hello, good morning), greet them back in a friendly way, use their name if it fits, and do not worry about the FAQ here.
        - If the question looks like a typo, gibberish, or is unclear (e.g. "asd", "asdiasndi", random letters), do not immediately say you cannot answer - instead, lightly say you did not understand or that it may be a typo, and ask them to repeat or clarify the question.
        - If the question is clear and related to class information (quiz, deadline, schedule, grades, etc.), answer ONLY based on the FAQ list above. Each FAQ may have a [Subject] label - this means it applies only to that subject. If there is no label, it applies to everyone.
        - If the question is clear but NO FAQ matches it (and it is not a greeting or typo/gibberish), answer with EXACTLY this and nothing else: UNSURE_NOTIFY: I cannot answer that right away, I will let Sir Francisco know so he can reply to you.
        - Never guess or make up an answer that is not in the FAQ list for questions about the class.

        AI:
        PROMPT;

        $lastError = null;

        foreach (self::GEMINI_MODEL_FALLBACK_CHAIN as $model) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        ['contents' => [['parts' => [['text' => $prompt]]]]]
                    );

                if ($response->failed()) {
                    $lastError = "HTTP {$response->status()}: {$response->body()}";
                    \Log::warning('Gemini model failed, trying next fallback', [
                        'model' => $model,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue; // subukan ang susunod na model sa chain
                }

                $text = trim($response->json('candidates.0.content.parts.0.text') ?? '');

                if ($text === '') {
                    $lastError = "Empty response body from {$model}";
                    \Log::warning('Gemini returned empty text, trying next fallback', ['model' => $model]);
                    continue;
                }

                // May gumana — log kung anong model talaga ang sumagot, useful
                // para malaman mo kung kailan mag-shi-shift sa fallback.
                \Log::info('Gemini model succeeded', ['model' => $model]);

                if (str_starts_with($text, 'UNSURE_NOTIFY:')) {
                    return [trim(str_replace('UNSURE_NOTIFY:', '', $text)), true];
                }

                return [$text, false];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                \Log::warning('Gemini model threw exception, trying next fallback', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Naubos na ang buong fallback chain, wala talagang gumana.
        \Log::error('All Gemini models failed', ['last_error' => $lastError]);

        return ['Sorry, the AI assistant ran into an error. Sir Francisco will look at your question instead.', true];
    }
}