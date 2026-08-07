<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuraPointLog extends Model
{
    const UPDATED_AT = null; // walang updated_at, log entries lang, hindi dapat baguhin

    protected $fillable = [
        'student_id', 'section_id', 'points', 'note', 'recorded_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}