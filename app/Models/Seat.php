<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = ['section_id', 'student_id', 'layout', 'position_x', 'position_y', 'seat_label'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}