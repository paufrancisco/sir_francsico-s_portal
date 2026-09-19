<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassmateMessage extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'body', 'hidden_at'];

    protected $casts = [
        'hidden_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'recipient_id');
    }
}