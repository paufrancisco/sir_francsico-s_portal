<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'section_id',
        'period',
        'title',
        'description',
        'attachment_url',
        'status',
        'date_covered',
        'has_quiz',
        'quiz_items',
        'has_tp',
        'sort_order',
        'archived_at',
    ];

    protected $casts = [
        'date_covered' => 'date',
        'has_quiz' => 'boolean',
        'has_tp' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
