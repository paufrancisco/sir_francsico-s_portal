<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'section_id'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}