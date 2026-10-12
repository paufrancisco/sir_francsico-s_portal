<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalPostImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'path', 'position'];
}
