<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'moodle_course_id',
        'moodle_course_name',
        'moodle_category_id',
        'moodle_summary',
        'sync_status',
        'sync_error',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the course associated with this mapping.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope to get only synced mappings.
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope to get only pending mappings.
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }
}
