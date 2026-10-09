<x-mail::message>
# {{ $needsReview ? 'Unusual question - your reply is needed' : 'New question in the class portal' }}

**Student:** {{ $student->full_name }} ({{ $student->student_number }})

---

@foreach ($transcript as $m)
**{{ $m->sender === 'student' ? $student->full_name : ($m->sender === 'admin' ? 'You' : 'AI Assistant') }}:**
{{ $m->body }}

@endforeach

---

<x-mail::button :url="url('/paulo/chat/' . $student->id)">
Open the chat
</x-mail::button>
</x-mail::message>