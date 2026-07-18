<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['student_id', 'section_id', 'question_text', 'answer_text', 'answered', 'answered_at'];

    protected function casts(): array
    {
        return ['answered' => 'boolean', 'answered_at' => 'datetime'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}