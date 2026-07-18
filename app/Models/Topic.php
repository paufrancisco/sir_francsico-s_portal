<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = ['section_id', 'title', 'description', 'attachment_url', 'status', 'date_covered'];

    protected function casts(): array
    {
        return ['date_covered' => 'date'];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}