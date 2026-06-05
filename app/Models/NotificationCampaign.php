<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'message',
        'type',
        'audience',
        'trigger_event',
        'is_active',
        'deliver_once',
        'starts_at',
        'ends_at',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deliver_once' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function scopeActiveForTrigger(Builder $query, string $trigger): Builder
    {
        return $query
            ->where('trigger_event', $trigger)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest();
    }
}