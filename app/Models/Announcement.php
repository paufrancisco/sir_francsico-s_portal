<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'is_global', 'posted_by'];

    protected $casts = ['is_global' => 'boolean'];

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'announcement_section');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}