<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentActivity extends Model
{
    const UPDATED_AT = null;

    const TYPES = [
        'grades_confirmed',
        'grades_recheck',
        'grades_viewed',
        'chat_message',
        'appointment_booked',
        'classmate_message',
    ];

    protected $fillable = ['student_id', 'section_id', 'type', 'summary', 'meta', 'read_at'];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public static function record(string $type, ?Student $student, string $summary, array $meta = [], int $dedupeMinutes = 0): void
    {
        try {
            if (! $student) {
                return;
            }

            if ($dedupeMinutes > 0 && static::where('student_id', $student->id)
                ->where('type', $type)
                ->where('created_at', '>=', now()->subMinutes($dedupeMinutes))
                ->exists()) {
                return;
            }

            static::create([
                'student_id' => $student->id,
                'section_id' => $student->section_id,
                'type' => $type,
                'summary' => Str::limit($summary, 250),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
