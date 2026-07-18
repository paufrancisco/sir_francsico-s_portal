<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['student_id', 'last_active_at'];
    protected $casts = ['last_active_at' => 'datetime'];
}