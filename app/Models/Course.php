<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    // Course website ka sellable product hai; Moodle course ID se learning content linked rehta hai.
    protected $fillable = [
        'category_id',
        'moodle_course_id',
        'moodle_shortname',
        'moodle_visible',
        'moodle_format',
        'moodle_start_at',
        'moodle_end_at',
        'moodle_synced_at',
        'title',
        'slug',
        'short_description',
        'thumbnail',
        'price',
        'currency',
        'level',
        'duration',
        'access_duration_days',
        'instructor_name',
        'curriculum',
        'requirements',
        'outcomes',
        'is_featured',
        'status',
        'is_admin_approved',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'access_duration_days' => 'integer',
            'curriculum' => 'array',
            'requirements' => 'array',
            'outcomes' => 'array',
            'is_featured' => 'boolean',
            'is_admin_approved' => 'boolean',
            'approved_at' => 'datetime',
            'moodle_visible' => 'boolean',
            'moodle_start_at' => 'datetime',
            'moodle_end_at' => 'datetime',
            'moodle_synced_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(CourseReview::class)->approved();
    }

    public function scopePublished(Builder $query): Builder
    {
        // Public pages par sirf admin-approved, published aur academy-visible courses show hote hain.
        return $query
            ->where('status', 'published')
            ->where('is_admin_approved', true)
            ->where('moodle_visible', true);
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status === 'published'
            && $this->is_admin_approved
            && $this->moodle_visible;
    }

    public function accessLabel(): string
    {
        return ((int) $this->access_duration_days) > 0
            ? $this->access_duration_days.' days access'
            : 'Lifetime access';
    }
}
