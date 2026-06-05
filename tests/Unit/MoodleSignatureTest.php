<?php

namespace Tests\Unit;

use App\Services\MoodleSignature;
use PHPUnit\Framework\TestCase;

class MoodleSignatureTest extends TestCase
{
    public function test_it_builds_moodle_compatible_canonical_payload(): void
    {
        $payload = [
            'username' => ' waqas ',
            'courseid' => 20,
            'suspended' => true,
            'meta' => ['b' => ' two ', 'a' => 'one'],
        ];

        $this->assertSame(
            'courseid=20&meta=%5Ba%3Aone%2Cb%3Atwo%5D&suspended=1&username=waqas',
            MoodleSignature::canonicalPayload($payload)
        );
    }

    public function test_it_creates_expected_sha256_hmac_signature(): void
    {
        $function = 'local_custom_webservice_enrol_user';
        $timestamp = 1710000000;
        $payload = ['courseid' => 20, 'username' => 'student_001'];
        $secret = 'top-secret';

        $expected = hash_hmac(
            'sha256',
            $function."\n".$timestamp."\n".'courseid=20&username=student_001',
            $secret
        );

        $this->assertSame($expected, MoodleSignature::create($function, $timestamp, $payload, $secret));
    }
}
