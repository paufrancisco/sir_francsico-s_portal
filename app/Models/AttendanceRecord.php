<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'section_id', 'student_id', 'seat_id',
        'attendance_date', 'status', 'marked_by',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}