<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Home page ke liye featured courses aur categories business catalog se aati hain.
        return view('home', [
            'featuredCourses' => Course::published()
                ->with('category')
                ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
                ->withCount('approvedReviews as approved_reviews_count')
                ->where('is_featured', true)
                ->latest()
                ->take(6)
                ->get(),
            'categories' => Category::where('status', 'active')
                ->whereHas('courses', fn ($course) => $course->published())
                ->withCount(['courses' => fn ($course) => $course->published()])
                ->get(),
        ]);
    }
}
