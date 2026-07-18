<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LockedSectionLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'section_type', 'success', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime', 'success' => 'boolean'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}