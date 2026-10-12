<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreedomWallReply extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['post_id', 'student_id', 'author_first_name', 'body'];

    public function post()
    {
        return $this->belongsTo(FreedomWallPost::class, 'post_id');
    }
}
