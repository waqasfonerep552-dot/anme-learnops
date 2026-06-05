<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseProgress extends Model
{
    // Moodle progress sync ka local snapshot, student dashboard speed ke liye useful hai.
    protected $table = 'course_progress';

    protected $fillable = ['user_id', 'course_id', 'progress', 'last_sync'];

    protected function casts(): array
    {
        return [
            'progress' => 'decimal:2',
            'last_sync' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
