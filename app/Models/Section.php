<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'schedule', 'grades_computed_at'];

    protected $casts = [
        'grades_computed_at' => 'datetime',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }
}