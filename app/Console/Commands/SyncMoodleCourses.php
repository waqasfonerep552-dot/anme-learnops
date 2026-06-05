<?php

namespace App\Console\Commands;

use App\Services\MoodleCourseSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncMoodleCourses extends Command
{
    protected $signature = 'moodle:sync-courses';

    protected $description = 'Sync Moodle categories and courses into the business catalog.';

    public function handle(MoodleCourseSyncService $sync): int
    {
        try {
            $summary = $sync->sync();
        } catch (Throwable $exception) {
            $this->error('Moodle sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            "Synced {$summary['courses']} academy courses ".
            "({$summary['created']} created, {$summary['updated']} updated) ".
            "and {$summary['categories']} categories."
        );

        return self::SUCCESS;
    }
}
