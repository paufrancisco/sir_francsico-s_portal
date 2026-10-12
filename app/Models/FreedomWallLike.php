<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreedomWallLike extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['post_id', 'client_id', 'student_id', 'author_first_name'];
}
