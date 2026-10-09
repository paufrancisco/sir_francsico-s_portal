<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLoginLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['student_id', 'student_number', 'source', 'success', 'ip', 'user_agent'];

    protected $casts = ['success' => 'boolean'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
