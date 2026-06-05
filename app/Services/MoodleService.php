<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MoodleService
{
    // Ye class website ka single Moodle REST gateway hai; saari Moodle API calls yahin se guzarti hain.

    public function createUser(array $user): array
    {
        return $this->call(config('moodle.functions.create_user'), $user);
    }

    public function enrolUser(array $payload): array
    {
        return $this->call(config('moodle.functions.enrol_user'), $payload);
    }

    public function suspendUser(array $payload): array
    {
        return $this->call(config('moodle.functions.suspend_user'), $payload);
    }

    public function getProgress(int $courseid, array $options = []): array
    {
        return $this->call(config('moodle.functions.user_progress'), array_merge([
            'courseid' => $courseid,
            'onlyactive' => 0,
            'limitfrom' => 0,
            'limitnumber' => 100,
        ], $options));
    }

    public function getCourses(): array
    {
        return $this->call(config('moodle.functions.courses'));
    }

    public function getCategories(): array
    {
        return $this->call(config('moodle.functions.categories'));
    }

    public function getUsersByField(string $field, array $values): array
    {
        // Moodle array params ko values[0], values[1] format mein expect karta hai.
        $payload = ['field' => $field];
        foreach (array_values($values) as $index => $value) {
            $payload["values[$index]"] = $value;
        }

        return $this->call(config('moodle.functions.users_by_field'), $payload);
    }

    public function updateUsers(array $users): array
    {
        return $this->call(config('moodle.functions.update_users'), ['users' => $users]);
    }

    public function call(string $function, array $payload = []): array
    {
        if (blank(config('moodle.token'))) {
            throw new RuntimeException('Moodle token is not configured.');
        }

        $payload = $this->signedPayload($function, $payload);

        // Moodle REST API mein URL same rehta hai; actual endpoint wsfunction se decide hota hai.
        $response = $this->client()
            ->asForm()
            ->post(config('moodle.rest_url'), array_merge([
                'wstoken' => config('moodle.token'),
                'moodlewsrestformat' => config('moodle.format'),
                'wsfunction' => $function,
            ], $payload));

        if (!$response->successful()) {
            throw new RuntimeException('Moodle request failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();
        if (isset($data['exception'])) {
            // Moodle exception ko app exception bana dete hain taake controllers/jobs clean rahen.
            throw new RuntimeException($data['message'] ?? 'Moodle returned an API exception.');
        }

        return is_array($data) ? $data : [];
    }

    private function client(): PendingRequest
    {
        return Http::timeout(config('moodle.timeout'))
            ->acceptJson();
    }

    private function signedPayload(string $function, array $payload): array
    {
        if (! $this->shouldSign($function)) {
            return $payload;
        }

        $secret = (string) config('moodle.hmac.secret', '');
        if ($secret === '') {
            throw new RuntimeException('Moodle HMAC is enabled but MOODLE_HMAC_SECRET is not configured.');
        }

        $timestamp = now()->timestamp;

        return array_merge($payload, [
            'requesttimestamp' => $timestamp,
            'signature' => MoodleSignature::create($function, $timestamp, $payload, $secret),
        ]);
    }

    private function shouldSign(string $function): bool
    {
        if (! config('moodle.hmac.enabled')) {
            return false;
        }

        return in_array($function, config('moodle.signed_functions', []), true);
    }
}
