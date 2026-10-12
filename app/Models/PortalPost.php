<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalPost extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['student_id', 'section_id', 'caption', 'layout', 'author_first_name'];

    public function images()
    {
        return $this->hasMany(PortalPostImage::class, 'post_id')->orderBy('position');
    }

    public function reactions()
    {
        return $this->hasMany(PortalPostReaction::class, 'post_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
