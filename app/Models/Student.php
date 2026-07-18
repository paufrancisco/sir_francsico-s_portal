<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $fillable = [
        'student_number', 'full_name', 'section_id',
        'password', 'grades_password', 'password_changed_at',
    ];

    protected $hidden = ['password', 'grades_password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'grades_password' => 'hashed',
            'password_changed_at' => 'datetime',
        ];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function seat()
    {
        return $this->hasOne(Seat::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function lockedSectionLogs()
    {
        return $this->hasMany(LockedSectionLog::class);
    }
}