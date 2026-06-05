<?php

namespace Tests\Feature;

use App\Services\MoodleService;
use App\Services\MoodleSignature;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MoodleServiceHmacTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_custom_write_endpoint_is_signed_when_hmac_is_enabled(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1710000000));
        $this->configureMoodleHmac(true, 'top-secret');

        Http::fake([
            '*' => Http::response(['status' => true, 'userid' => 10], 200),
        ]);

        app(MoodleService::class)->createUser([
            'username' => 'student_001',
            'password' => 'StrongPass123!',
            'firstname' => 'Test',
            'lastname' => 'Student',
            'email' => 'student@example.com',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            parse_str($request->body(), $payload);

            $businessPayload = [
                'username' => 'student_001',
                'password' => 'StrongPass123!',
                'firstname' => 'Test',
                'lastname' => 'Student',
                'email' => 'student@example.com',
            ];

            return $payload['wsfunction'] === 'local_custom_webservice_create_user'
                && $payload['requesttimestamp'] === '1710000000'
                && $payload['signature'] === MoodleSignature::create(
                    'local_custom_webservice_create_user',
                    1710000000,
                    $businessPayload,
                    'top-secret'
                );
        });
    }

    public function test_builtin_endpoint_is_not_signed_even_when_hmac_is_enabled(): void
    {
        $this->configureMoodleHmac(true, 'top-secret');

        Http::fake([
            '*' => Http::response([], 200),
        ]);

        app(MoodleService::class)->getCourses();

        Http::assertSent(function (HttpRequest $request): bool {
            parse_str($request->body(), $payload);

            return $payload['wsfunction'] === 'core_course_get_courses'
                && ! array_key_exists('requesttimestamp', $payload)
                && ! array_key_exists('signature', $payload);
        });
    }

    public function test_hmac_enabled_requires_secret(): void
    {
        $this->configureMoodleHmac(true, '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MOODLE_HMAC_SECRET');

        app(MoodleService::class)->createUser([
            'username' => 'student_001',
            'password' => 'StrongPass123!',
            'firstname' => 'Test',
            'lastname' => 'Student',
            'email' => 'student@example.com',
        ]);
    }

    private function configureMoodleHmac(bool $enabled, string $secret): void
    {
        config([
            'moodle.rest_url' => 'https://academy.test/webservice/rest/server.php',
            'moodle.token' => 'test-token',
            'moodle.format' => 'json',
            'moodle.hmac.enabled' => $enabled,
            'moodle.hmac.secret' => $secret,
            'moodle.functions.create_user' => 'local_custom_webservice_create_user',
            'moodle.functions.courses' => 'core_course_get_courses',
            'moodle.signed_functions' => [
                'local_custom_webservice_create_user',
                'local_custom_webservice_enrol_user',
                'local_custom_webservice_suspend_user',
            ],
        ]);
    }
}
