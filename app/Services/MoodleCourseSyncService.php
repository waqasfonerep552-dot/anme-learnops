<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MoodleCourseSyncService
{
    public function __construct(private readonly MoodleService $moodle)
    {
    }

    public function sync(): array
    {
        // Moodle learning source hai; business website catalog hai. Sync sirf mapping/metadata fresh karta hai.
        $categories = $this->moodle->getCategories();
        $courses = $this->moodle->getCourses();
        $categoryCount = $this->syncCategories($categories);

        $summary = [
            'categories' => $categoryCount,
            'courses' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        foreach ($courses as $courseData) {
            $moodleCourseId = (int) ($courseData['id'] ?? 0);

            if ($moodleCourseId <= 1) {
                // Moodle ka site/frontpage course business catalog mein sell nahi hota.
                $summary['skipped']++;
                continue;
            }

            $course = Course::firstOrNew(['moodle_course_id' => $moodleCourseId]);
            $wasRecentlyCreated = ! $course->exists;
            $category = Category::where('moodle_category_id', $courseData['categoryid'] ?? null)->first();
            $moodleTitle = $this->titleFromMoodle($courseData, $moodleCourseId);
            $summaryText = $this->summaryFromMoodle($courseData);
            $visible = (bool) ($courseData['visible'] ?? true);

            $course->fill([
                'category_id' => $category?->id,
                'moodle_shortname' => $courseData['shortname'] ?? null,
                'moodle_visible' => $visible,
                'moodle_format' => $courseData['format'] ?? null,
                'moodle_start_at' => $this->timestampToCarbon($courseData['startdate'] ?? null),
                'moodle_end_at' => $this->timestampToCarbon($courseData['enddate'] ?? null),
                'moodle_synced_at' => now(),
                // Existing business fields preserve karte hain, warna admin ke prices/content overwrite ho jayenge.
                'title' => $course->exists && filled($course->title) ? $course->title : $moodleTitle,
                'slug' => $course->exists ? $course->slug : $this->uniqueCourseSlug($moodleTitle, $moodleCourseId),
                'short_description' => $course->exists && filled($course->short_description)
                    ? $course->short_description
                    : $summaryText,
                'price' => $course->exists ? $course->price : 0,
                'currency' => $course->exists ? $course->currency : 'PKR',
                'status' => $course->exists ? $course->status : 'draft',
                'is_admin_approved' => $course->exists ? $course->is_admin_approved : false,
            ]);

            $course->save();

            $summary['courses']++;
            $summary[$wasRecentlyCreated ? 'created' : 'updated']++;
        }

        return $summary;
    }

    private function syncCategories(array $categories): int
    {
        // Categories Moodle se aati hain taake website filters real LMS structure follow karein.
        $count = 0;

        foreach ($categories as $categoryData) {
            if (empty($categoryData['id']) || empty($categoryData['name'])) {
                continue;
            }

            $moodleCategoryId = (int) $categoryData['id'];

            Category::updateOrCreate([
                'moodle_category_id' => $moodleCategoryId,
            ], [
                'name' => $categoryData['name'],
                'slug' => $this->uniqueCategorySlug($categoryData['name'], $moodleCategoryId),
                'status' => 'active',
            ]);

            $count++;
        }

        return $count;
    }

    private function titleFromMoodle(array $courseData, int $moodleCourseId): string
    {
        return $courseData['fullname']
            ?? $courseData['displayname']
            ?? $courseData['shortname']
            ?? 'Moodle Course '.$moodleCourseId;
    }

    private function summaryFromMoodle(array $courseData): string
    {
        // Moodle summary HTML ho sakti hai, isliye public catalog ke liye clean text bana rahe hain.
        $summary = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($courseData['summary'] ?? ''))));

        return $summary !== '' ? $summary : 'Professional Moodle training course synced into the business catalog.';
    }

    private function timestampToCarbon(mixed $timestamp): ?Carbon
    {
        $timestamp = (int) $timestamp;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : null;
    }

    private function uniqueCourseSlug(string $title, int $moodleCourseId): string
    {
        $slug = Str::slug($title) ?: 'course-'.$moodleCourseId;

        return Course::where('slug', $slug)->exists() ? $slug.'-'.$moodleCourseId : $slug;
    }

    private function uniqueCategorySlug(string $title, int $moodleCategoryId): string
    {
        $slug = Str::slug($title) ?: 'category-'.$moodleCategoryId;
        $existing = Category::where('slug', $slug)
            ->where('moodle_category_id', '!=', $moodleCategoryId)
            ->exists();

        return $existing ? $slug.'-'.$moodleCategoryId : $slug;
    }
}
