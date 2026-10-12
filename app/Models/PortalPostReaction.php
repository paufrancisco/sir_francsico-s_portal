<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalPostReaction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['post_id', 'actor', 'device_id', 'student_id', 'author_first_name', 'reaction'];
}
