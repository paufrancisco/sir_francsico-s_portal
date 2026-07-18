<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['student_id', 'section_id', 'sender', 'body', 'needs_review'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}