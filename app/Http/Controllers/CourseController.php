<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        // Public catalog sirf published business courses show karta hai, Moodle hidden data direct expose nahi hota.
        $sort = $request->query('sort', 'latest');
        $courses = Course::query()
            ->published()
            ->with('category')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews as approved_reviews_count')
            ->when($request->query('q'), function ($query, $q): void {
                $query->where(function ($query) use ($q): void {
                    $query->where('title', 'like', '%'.$q.'%')
                        ->orWhere('short_description', 'like', '%'.$q.'%')
                        ->orWhere('instructor_name', 'like', '%'.$q.'%');
                });
            })
            ->when($request->query('category'), fn ($query, $slug) => $query->whereHas('category', fn ($category) => $category->where('slug', $slug)))
            ->when($request->query('level'), fn ($query, $level) => $query->where('level', $level))
            ->when($request->filled('min_price'), fn ($query) => $query->where('price', '>=', (float) $request->query('min_price')))
            ->when($request->filled('max_price'), fn ($query) => $query->where('price', '<=', (float) $request->query('max_price')))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->when($sort === 'price_low', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'featured', fn ($query) => $query->orderByDesc('is_featured')->latest())
            ->when(! in_array($sort, ['price_low', 'price_high', 'title', 'featured'], true), fn ($query) => $query->latest())
            ->paginate(9)
            ->withQueryString();

        $levels = Course::query()
            ->published()
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->distinct()
            ->orderBy('level')
            ->pluck('level');

        return view('courses.index', [
            'courses' => $courses,
            'categories' => Category::where('status', 'active')
                ->whereHas('courses', fn ($course) => $course->published())
                ->get(),
            'levels' => $levels,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        // Direct URL se unapproved/draft/hidden course open karne ki koshish ho to 404.
        abort_unless($course->isPubliclyAvailable(), 404);

        $course->load([
            'category',
            'approvedReviews' => fn ($query) => $query->with('user')->latest()->take(8),
        ])->loadAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->loadCount('approvedReviews as approved_reviews_count');

        $canReview = false;
        $existingReview = null;

        if ($request->user()) {
            $canReview = $request->user()->enrollments()
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->exists()
                || $request->user()->orders()
                    ->where('status', 'paid')
                    ->whereHas('items', fn ($item) => $item->where('course_id', $course->id))
                    ->exists();

            $existingReview = CourseReview::where('course_id', $course->id)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        return view('courses.show', [
            'course' => $course,
            'canReview' => $canReview,
            'existingReview' => $existingReview,
        ]);
    }
}
