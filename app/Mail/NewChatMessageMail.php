<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NewChatMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public Collection $transcript,
        public bool $needsReview,
        public array $studentContext = [],
    ) {
    }

    public function build()
    {
        $subject = $this->subjectLine();

        return $this->subject($subject)->markdown('mail.new-chat-message', [
            'studentContext' => $this->studentContext,
        ]);
    }

    private function subjectLine(): string
    {
        $label = $this->needsReview ? 'Kakaibang tanong' : 'Bagong tanong sa chat';

        $subjectName = $this->studentContext['subject'] ?? null;
        $sectionName = $this->studentContext['section'] ?? null;

        $classTag = ($subjectName && $subjectName !== '—')
            ? " ({$subjectName}" . ($sectionName && $sectionName !== '—' ? " / {$sectionName}" : '') . ')'
            : '';

        return "{$label} — {$this->student->full_name}{$classTag}";
    }
}