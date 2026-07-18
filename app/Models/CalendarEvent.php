<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = ['section_id', 'event_date', 'type', 'title', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}