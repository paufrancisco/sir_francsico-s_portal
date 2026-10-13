<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkImportLog extends Model
{
    // Reminder: new columns must be added here too (mass assignment)
    protected $fillable = [
        'first_name',
        'period',
        'student_count',
        'dry_run',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
    ];
}
