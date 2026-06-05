<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    // Admin audit trail: payments, fulfilment aur Moodle sync events yahan trace hote hain.
    protected $fillable = [
        'user_id',
        'action',
        'payload',
        'ip_address',
        'country_code',
        'country_name',
        'city',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'http_method',
        'route_name',
        'url',
        'referer',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
