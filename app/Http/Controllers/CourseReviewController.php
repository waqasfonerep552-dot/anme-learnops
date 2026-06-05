<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseReview;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseReviewController extends Controller
{
    public function store(Request $request, Course $course, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($course->isPubliclyAvailable(), 404);
        abort_unless($this->canReview($request, $course), 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $review = CourseReview::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            [
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'status' => 'pending',
                'is_featured' => false,
                'reviewed_at' => now(),
                'approved_at' => null,
                'approved_by' => null,
            ]
        );

        $activity->log('review.submitted', [
            'course_id' => $course->id,
            'course_title' => $course->title,
            'review_id' => $review->id,
            'rating' => $review->rating,
        ]);

        return back()->with('status', 'Review submitted. It will appear publicly after admin approval.');
    }

    private function canReview(Request $request, Course $course): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $hasActiveEnrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        $hasPaidOrder = $user->orders()
            ->where('status', 'paid')
            ->whereHas('items', fn ($item) => $item->where('course_id', $course->id))
            ->exists();

        return $hasActiveEnrollment || $hasPaidOrder;
    }
}
