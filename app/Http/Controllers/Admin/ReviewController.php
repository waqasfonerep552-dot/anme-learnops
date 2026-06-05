<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        // Admin reviews ko approve/reject karta hai taake public course pages par sirf quality feedback aaye.
        $reviews = CourseReview::query()
            ->with(['course.category', 'user', 'approvedBy'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%')
                        ->orWhereHas('course', fn ($course) => $course->where('title', 'like', '%'.$search.'%'))
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, CourseReview $courseReview, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $courseReview->update([
            'status' => $data['status'],
            'is_featured' => $request->boolean('is_featured'),
            'approved_at' => $data['status'] === 'approved' ? now() : null,
            'approved_by' => $data['status'] === 'approved' ? $request->user()->id : null,
        ]);

        $activity->log('review.moderated', [
            'review_id' => $courseReview->id,
            'course_id' => $courseReview->course_id,
            'status' => $courseReview->status,
            'is_featured' => $courseReview->is_featured,
        ]);

        return back()->with('status', 'Review moderation updated.');
    }

    public function destroy(CourseReview $courseReview, ActivityLogger $activity): RedirectResponse
    {
        $activity->log('review.deleted', [
            'review_id' => $courseReview->id,
            'course_id' => $courseReview->course_id,
        ]);

        $courseReview->delete();

        return back()->with('status', 'Review deleted successfully.');
    }
}
