<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'is_booked',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_booked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function appointment(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Slots that are open for booking: active, not booked, and today or later.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_active', true)
            ->where('is_booked', false)
            ->whereDate('date', '>=', now()->toDateString());
    }
}