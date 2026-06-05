<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    // Enrollment business order aur Moodle course access ke beech bridge record hai.
    protected $fillable = [
        'user_id',
        'course_id',
        'order_id',
        'enrolled_at',
        'access_starts_at',
        'access_ends_at',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'access_starts_at' => 'datetime',
            'access_ends_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
