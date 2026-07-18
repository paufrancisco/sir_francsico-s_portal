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
    ) {
    }

    public function build()
    {
        $subject = $this->needsReview
            ? 'Kakaibang tanong — ' . $this->student->full_name
            : 'Bagong tanong sa chat — ' . $this->student->full_name;

        return $this->subject($subject)->markdown('mail.new-chat-message');
    }
}