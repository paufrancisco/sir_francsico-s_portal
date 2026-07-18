<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeCorrection extends Model
{
    protected $fillable = [
        'student_id', 'section_id', 'type', 'notes', 'status', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}