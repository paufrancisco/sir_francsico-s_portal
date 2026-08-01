<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'faculty_availability_id',
        'appointment_date',
        'start_time',
        'end_time',
        'reason',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(FacultyAvailability::class, 'faculty_availability_id');
    }
}