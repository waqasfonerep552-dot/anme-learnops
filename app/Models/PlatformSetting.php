<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    // Admin settings DB mein save hoti hain; public values cache hoti hain speed ke liye.
    protected $fillable = ['key', 'value', 'is_private'];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param array<string, string|null> $defaults
     * @return array<string, string|null>
     */
    public static function publicValues(array $defaults = []): array
    {
        // Public settings cache se layout/footer/home page ko fast data milta hai.
        $settings = Cache::remember('platform_settings_public', now()->addMinutes(10), function (): array {
            return static::query()
                ->where('is_private', false)
                ->pluck('value', 'key')
                ->all();
        });

        return array_merge($defaults, $settings);
    }

    public static function setValue(string $key, ?string $value, bool $private = false): self
    {
        // Setting change hote hi cache clear karna zaroori hai warna old text show hoga.
        Cache::forget('platform_settings_public');

        return static::updateOrCreate(['key' => $key], [
            'value' => $value,
            'is_private' => $private,
        ]);
    }
}
