<?php

namespace App\Services;

class MoodleSignature
{
    /**
     * Moodle plugin ke HMAC helper jaisa exact message banata hai:
     * wsfunction + newline + timestamp + newline + canonical payload.
     */
    public static function create(string $function, int $timestamp, array $payload, string $secret): string
    {
        return hash_hmac('sha256', $function."\n".$timestamp."\n".self::canonicalPayload($payload), $secret);
    }

    public static function canonicalPayload(array $payload): string
    {
        ksort($payload);

        $pairs = [];
        foreach ($payload as $key => $value) {
            $pairs[] = rawurlencode((string) $key).'='.rawurlencode(self::normaliseValue($value));
        }

        return implode('&', $pairs);
    }

    private static function normaliseValue(mixed $value): string
    {
        if (is_array($value)) {
            ksort($value);

            $items = [];
            foreach ($value as $key => $item) {
                $items[] = (string) $key.':'.self::normaliseValue($item);
            }

            return '['.implode(',', $items).']';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }
}
