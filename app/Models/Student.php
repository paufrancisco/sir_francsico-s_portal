<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Student extends Authenticatable
{
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path
            ? Storage::disk('supabase')->url($this->photo_path)
            : null;
    }

    protected $fillable = [
        'student_number', 'full_name', 'section_id',
        'password', 'password_changed_at', 'photo_path',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
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