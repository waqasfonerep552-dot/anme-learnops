<?php

namespace App\Services;

use Throwable;

class MoodleConnectionTester
{
    /**
     * @return array<int, array{name: string, function: string, type: string, status: string, message: string}>
     */
    public function run(MoodleService $moodle): array
    {
        // Diagnostics read-only rakhe hain; ye users/enrolments create ya modify nahi karta.
        $checks = [
            [
                'name' => 'Core courses',
                'function' => config('moodle.functions.courses'),
                'type' => 'built-in',
                'payload' => [],
            ],
            [
                'name' => 'Core categories',
                'function' => config('moodle.functions.categories'),
                'type' => 'built-in',
                'payload' => [],
            ],
            [
                'name' => 'Custom progress endpoint',
                'function' => config('moodle.functions.user_progress'),
                'type' => 'custom plugin',
                'payload' => [
                    'courseid' => 2,
                    'onlyactive' => 0,
                    'limitfrom' => 0,
                    'limitnumber' => 1,
                ],
            ],
        ];

        return collect($checks)->map(function (array $check) use ($moodle): array {
            try {
                $result = $moodle->call($check['function'], $check['payload']);

                return [
                    'name' => $check['name'],
                    'function' => $check['function'],
                    'type' => $check['type'],
                    'status' => 'ok',
                    'message' => 'Connected. Response items: '.$this->countItems($result),
                ];
            } catch (Throwable $exception) {
                return [
                    'name' => $check['name'],
                    'function' => $check['function'],
                    'type' => $check['type'],
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        })->values()->all();
    }

    private function countItems(array $result): int
    {
        if (isset($result['courses']) && is_array($result['courses'])) {
            return count($result['courses']);
        }

        if (isset($result['categories']) && is_array($result['categories'])) {
            return count($result['categories']);
        }

        if (isset($result['users']) && is_array($result['users'])) {
            return count($result['users']);
        }

        return count($result);
    }
}
