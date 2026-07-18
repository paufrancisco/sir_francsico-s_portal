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

    public function verify(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (! $student || $student->password !== $request->password) {
            return response()->json(['message' => 'Mali ang student number o password.'], 422);
        }

        $cutoff = now()->subMinutes(self::SESSION_WINDOW_MINUTES);
        $activeSessions = ChatSession::where('last_active_at', '>=', $cutoff)->get();
        $alreadyActive = $activeSessions->firstWhere('student_id', $student->id);

        if (! $alreadyActive && $activeSessions->count() >= self::MAX_CONCURRENT_CHATTERS) {
            return response()->json([
                'message' => 'Busy pa si AI assistant — may 3 estudyante pa na kausap niya ngayon. Subukan mo ulit sa loob ng ilang minuto. Samantala, baka nasa FAQ o Announcements na makikita mo yung sagot sa tanong mo!',
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

        if (config('services.admin_notify_email')) {
            $transcript = Message::where('student_id', $student->id)
                ->orderBy('created_at')
                ->get(['sender', 'body', 'created_at']);

            Mail::to(config('services.admin_notify_email'))
                ->send(new NewChatMessageMail($student, $transcript, $needsReview));
        }

        return response()->json([
            'messages' => [$studentMessage, $aiMessage],
        ]);
    }

    private function askGemini(Student $student, string $question): array
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return ['Pasensya, hindi pa naka-configure ang AI assistant. Titingnan na lang ni Sir Francisco ang tanong mo.', true];
        }

        $siblingSectionIds = $student->section
            ? \App\Models\Section::where('name', $student->section->name)->pluck('id')
            : collect();

        $faqs = Faq::whereNull('section_id')
            ->orWhereIn('section_id', $siblingSectionIds)
            ->with('section')
            ->get(['id', 'question', 'answer', 'section_id']);

        $faqText = $faqs->isEmpty()
            ? '(wala pang FAQ na naka-set)'
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
        Ikaw ay si "AI Assistant", ang chat assistant ni Sir Francisco sa kanyang class portal. Ang kausap mo ngayon ay si {$studentFirstName}.

        FAQ list (basehan mo lang ito sa mga impormasyon tungkol sa klase, quiz, deadline, atbp.):
        {$faqText}

        Kasaysayan ng usapan:
        {$historyText}

        {$studentFirstName}: {$question}

        Paano ka dapat kumilos:
        - Kausap mo ay tao, hindi FAQ lookup machine — makipag-usap ka nang natural, parang totoong AI assistant, gamit ang pangalan niya paminsan-minsan.
        - Kung greeting lang (hi, hello, kamusta, good morning), bumati ka pabalik nang palakaibigan, tawagin mo siya sa pangalan kung bagay, huwag mag-alala tungkol sa FAQ dito.
        - Kung mukhang typo, gibberish, o hindi malinaw ang tanong (hal. "asd", "asdiasndi", random letters), huwag mo agad sabihing "hindi ko masagot" — sa halip, sabihin mo nang magaan na parang hindi mo naintindihan o baka na-typo, at hilingin mong ulitin o linawin ang tanong.
        - Kung malinaw ang tanong at may kaugnayan ito sa impormasyon ng klase (quiz, deadline, schedule, grades, atbp.), sagutin mo LAMANG base sa FAQ list sa itaas. Ang bawat FAQ ay maaaring may [Subject] label — ibig sabihin partikular ito sa subject na iyon. Kung walang label, para ito sa lahat.
        - Kung malinaw ang tanong pero WALA itong FAQ na tumutugma (at hindi ito greeting o typo/gibberish), sagutin mo ng EKSAKTONG ganito, walang ibang idagdag: UNSURE_NOTIFY: Hindi ko pa masagot yan agad, ipapaalam ko kay Sir Francisco para siya na ang sumagot sa'yo.
        - Huwag kailanman manghula o gumawa ng sagot na wala sa FAQ list para sa mga tanong tungkol sa klase.

        AI:
        PROMPT;

        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent',
                    ['contents' => [['parts' => [['text' => $prompt]]]]]
                );

            if ($response->failed()) {
                \Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['Pasensya, may error sa AI assistant. Titingnan na lang ni Sir Francisco ang tanong mo.', true];
            }

            $text = trim($response->json('candidates.0.content.parts.0.text') ?? '');

            if (str_starts_with($text, 'UNSURE_NOTIFY:')) {
                return [trim(str_replace('UNSURE_NOTIFY:', '', $text)), true];
            }

            return [$text ?: 'Titingnan na lang ni Sir Francisco ang tanong mo.', $text === ''];
        } catch (\Throwable $e) {
            \Log::error('Gemini exception', ['message' => $e->getMessage()]);
            return ['Pasensya, may error sa AI assistant. Titingnan na lang ni Sir Francisco ang tanong mo.', true];
        }
    }
}