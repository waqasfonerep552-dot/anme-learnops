<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use App\Services\MoodleService;
use Illuminate\Console\Command;
use Throwable;

class SyncMoodleProgress extends Command
{
    protected $signature = 'moodle:sync-progress {--course_id=}';

    protected $description = 'Sync course progress from Moodle custom progress API into the business platform.';

    public function handle(MoodleService $moodle): int
    {
        // Optional course_id se specific course sync ho sakta hai, warna sab courses process hote hain.
        $courses = Course::query()
            ->when($this->option('course_id'), fn ($query, $id) => $query->where('id', $id))
            ->get();

        foreach ($courses as $course) {
            try {
                // Custom Moodle progress endpoint se course users/progress report nikalte hain.
                $report = $moodle->getProgress($course->moodle_course_id);
            } catch (Throwable $exception) {
                $this->error($course->title.': '.$exception->getMessage());
                continue;
            }

            foreach (($report['users'] ?? []) as $row) {
                $email = $row['email'] ?? null;
                if (!$email) {
                    continue;
                }

                $user = User::where('email', $email)->first();
                if (!$user) {
                    // Agar Moodle user website account se linked nahi hai to progress skip karte hain.
                    continue;
                }

                // Progress table updateOrCreate se duplicate rows avoid hoti hain.
                CourseProgress::updateOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ], [
                    'progress' => (float) ($row['progress'] ?? 0),
                    'last_sync' => now(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
