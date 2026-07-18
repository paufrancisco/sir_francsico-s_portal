<x-mail::message>
# {{ $needsReview ? 'Kakaibang tanong — kailangan mo ng sagot' : 'Bagong tanong sa class portal' }}

**Estudyante:** {{ $student->full_name }} ({{ $student->student_number }})

---

@foreach ($transcript as $m)
**{{ $m->sender === 'student' ? $student->full_name : ($m->sender === 'admin' ? 'Ikaw' : 'AI Assistant') }}:**
{{ $m->body }}

@endforeach

---

<x-mail::button :url="url('/paulo/chat/' . $student->id)">
Buksan ang chat
</x-mail::button>
</x-mail::message>