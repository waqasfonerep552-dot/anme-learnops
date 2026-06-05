<?php

namespace App\Console\Commands;

use App\Services\MoodleConnectionTester;
use App\Services\MoodleService;
use Illuminate\Console\Command;

class CheckMoodleConnection extends Command
{
    protected $signature = 'moodle:check';

    protected $description = 'Check Moodle API connectivity and required webservice functions.';

    public function handle(MoodleConnectionTester $tester, MoodleService $moodle): int
    {
        $this->info('Checking Moodle API connection...');

        $checks = $tester->run($moodle);

        $this->table(
            ['Check', 'Function', 'Type', 'Status', 'Message'],
            collect($checks)->map(fn (array $check): array => [
                $check['name'],
                $check['function'],
                $check['type'],
                $check['status'],
                $check['message'],
            ])->all()
        );

        $failed = collect($checks)->contains(fn (array $check): bool => $check['status'] !== 'ok');

        if ($failed) {
            $this->warn('Some Moodle checks failed. Token/service/functions ko Moodle admin mein verify karo.');

            return self::FAILURE;
        }

        $this->info('Moodle API checks passed.');

        return self::SUCCESS;
    }
}