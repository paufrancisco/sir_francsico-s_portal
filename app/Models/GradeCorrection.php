<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeCorrection extends Model
{
    protected $fillable = [
        'student_id', 'section_id', 'type', 'period', 'notes',
        'edited_items', 'attachment_path', 'status', 'resolved_at',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path
            ? \Illuminate\Support\Facades\Storage::disk('supabase')->url($this->attachment_path)
            : null;
    }

    protected $casts = [
        'edited_items' => 'array',
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