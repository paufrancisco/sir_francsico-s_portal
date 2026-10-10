<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreedomWallPost extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['student_id', 'section_id', 'body', 'color'];

    public function likes()
    {
        return $this->hasMany(FreedomWallLike::class, 'post_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
