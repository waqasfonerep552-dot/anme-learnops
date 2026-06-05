<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Services\MoodleCourseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        // Admin catalog mein Moodle mapping ke sath business pricing/visibility manage hoti hai.
        $visibility = $request->query('visibility');

        $courses = Course::query()
            ->with('category')
            ->withCount('enrollments')
            ->when($visibility, function ($query) use ($visibility): void {
                match ($visibility) {
                    'public' => $query
                        ->where('status', 'published')
                        ->where('is_admin_approved', true)
                        ->where('moodle_visible', true),
                    'needs_approval' => $query->where('is_admin_approved', false),
                    'draft' => $query->where('status', 'draft'),
                    'archived' => $query->where('status', 'archived'),
                    default => null,
                };
            })
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('short_description', 'like', '%'.$search.'%')
                        ->orWhere('moodle_shortname', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
            'courseStats' => [
                'total' => Course::count(),
                'public' => Course::published()->count(),
                'needs_approval' => Course::where('status', 'published')->where('is_admin_approved', false)->count(),
                'hidden_in_academy' => Course::where('moodle_visible', false)->count(),
            ],
        ]);
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course,
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        // Ye update Moodle content ko touch nahi karta, sirf business catalog fields update karta hai.
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'level' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'access_duration_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:published,draft,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'is_admin_approved' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ]);

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_admin_approved'] = $request->boolean('is_admin_approved');
        $data['title'] = filled($data['title'] ?? null) ? $data['title'] : $course->title;
        $data['price'] = filled($data['price'] ?? null) ? $data['price'] : 0;
        $data['currency'] = filled($data['currency'] ?? null) ? strtoupper($data['currency']) : ($course->currency ?: 'PKR');
        $data['status'] = filled($data['status'] ?? null) ? $data['status'] : 'draft';
        $data['access_duration_days'] = filled($data['access_duration_days'] ?? null) ? (int) $data['access_duration_days'] : 0;

        if ($data['is_admin_approved'] && ! $course->is_admin_approved) {
            $data['approved_at'] = now();
            $data['approved_by'] = $request->user()->id;
        }

        if (! $data['is_admin_approved']) {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }

        unset($data['thumbnail'], $data['remove_thumbnail']);

        if ($request->boolean('remove_thumbnail') && $course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
            $data['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $data['thumbnail'] = $request->file('thumbnail')->store('course-thumbnails', 'public');
        }

        $course->update($data);

        return redirect()->route('admin.courses.index')->with('status', 'Course updated successfully.');
    }

    public function approvePublic(Request $request, Course $course): RedirectResponse
    {
        if (! $course->moodle_visible) {
            return back()->with('error', 'This course is hidden inside ANME Academy. Make it visible there or sync again before public approval.');
        }

        $course->update([
            'status' => 'published',
            'is_admin_approved' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('status', $course->title.' is now approved for public catalog and checkout.');
    }

    public function sync(MoodleCourseSyncService $sync): RedirectResponse
    {
        try {
            // Moodle se latest course/category metadata pull karte hain.
            $summary = $sync->sync();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Academy course sync failed. Please check academy diagnostics and token permissions.');
        }

        return redirect()->route('admin.courses.index')->with(
            'status',
            "Academy sync complete: {$summary['courses']} courses, {$summary['categories']} categories, ".
            "{$summary['created']} new, {$summary['updated']} updated."
        );
    }
}
