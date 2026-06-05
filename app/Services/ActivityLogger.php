<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, array $payload = [], ?User $user = null, ?Request $request = null): ActivityLog
    {
        $request ??= request();
        $user ??= Auth::user();
        $userAgent = (string) $request->userAgent();
        $location = $this->locationFromRequest($request);

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'country_code' => $location['country_code'],
            'country_name' => $location['country_name'],
            'city' => $location['city'],
            'user_agent' => $userAgent,
            'device_type' => $this->deviceType($userAgent),
            'browser' => $this->browser($userAgent),
            'platform' => $this->platform($userAgent),
            'http_method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'referer' => $request->headers->get('referer'),
        ]);
    }

    /**
     * Geo data headers se pick hota hai. Real production mein Cloudflare/proxy
     * country headers ya GeoIP service connect kar sakte hain.
     *
     * @return array{country_code: string|null, country_name: string|null, city: string|null}
     */
    private function locationFromRequest(Request $request): array
    {
        $countryCode = $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('X-Country-Code')
            ?: $request->headers->get('X-AppEngine-Country')
            ?: $request->headers->get('X-Forwarded-Country');

        $city = $request->headers->get('X-City')
            ?: $request->headers->get('X-AppEngine-City');

        if (!$countryCode && $this->isPrivateOrLocalIp((string) $request->ip())) {
            return [
                'country_code' => 'LOCAL',
                'country_name' => 'Local network',
                'city' => $city,
            ];
        }

        return [
            'country_code' => $countryCode ? strtoupper($countryCode) : null,
            'country_name' => $countryCode ? strtoupper($countryCode) : null,
            'city' => $city,
        ];
    }

    private function isPrivateOrLocalIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function deviceType(string $userAgent): string
    {
        return match (true) {
            preg_match('/bot|crawler|spider|slurp/i', $userAgent) === 1 => 'bot',
            preg_match('/tablet|ipad/i', $userAgent) === 1 => 'tablet',
            preg_match('/mobile|iphone|android/i', $userAgent) === 1 => 'mobile',
            default => 'desktop',
        };
    }

    private function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown',
        };
    }

    private function platform(string $userAgent): string
    {
        return match (true) {
            stripos($userAgent, 'Windows') !== false => 'Windows',
            stripos($userAgent, 'Mac OS') !== false => 'macOS',
            stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false => 'iOS',
            stripos($userAgent, 'Android') !== false => 'Android',
            stripos($userAgent, 'Linux') !== false => 'Linux',
            default => 'Unknown',
        };
    }
}
