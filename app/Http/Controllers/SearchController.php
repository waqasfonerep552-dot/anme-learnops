<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function live(Request $request): JsonResponse
    {
        // Navbar live search: lightweight results, taake user full search page khole bina quickly jump kar sake.
        $search = trim((string) $request->query('q', ''));
        $isAdmin = $request->user()?->isAdmin() ?? false;

        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $courses = Course::query()
            ->with('category')
            ->when(! $isAdmin, fn ($query) => $query->published())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('moodle_shortname', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Course $course): array => [
                'type' => 'Course',
                'title' => $course->title,
                'subtitle' => trim(($course->category?->name ?? 'Course').' · '.$course->currency.' '.number_format((float) $course->price)),
                'url' => $isAdmin
                    ? route('admin.courses.index', ['q' => $course->title])
                    : route('courses.show', $course),
            ]);

        $results = $courses;

        if ($isAdmin) {
            $orders = Order::query()
                ->with(['user', 'payment'])
                ->where(function ($query) use ($search): void {
                    $query->where('order_no', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('payment', fn ($payment) => $payment
                            ->where('reference_no', 'like', '%'.$search.'%')
                            ->orWhere('transaction_id', 'like', '%'.$search.'%'));
                })
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (Order $order): array => [
                    'type' => 'Order',
                    'title' => $order->order_no,
                    'subtitle' => ($order->user?->name ?? 'Unknown student').' · '.strtoupper($order->payment?->status ?? $order->status),
                    'url' => route('admin.orders.index', ['q' => $order->order_no]),
                ]);

            $students = User::query()
                ->whereHas('roles', fn ($role) => $role->where('name', 'Student'))
                ->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                })
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (User $user): array => [
                    'type' => 'Student',
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'url' => route('admin.students.index', ['q' => $user->email]),
                ]);

            $results = $results->concat($orders)->concat($students);
        }

        return response()->json([
            'results' => $results->take(8)->values(),
        ]);
    }

    public function index(Request $request): View
    {
        // Global search public users ko approved courses dikhata hai; admin ko business records bhi milte hain.
        $search = trim((string) $request->query('q', ''));
        $isAdmin = $request->user()?->isAdmin() ?? false;

        $courses = Course::query()
            ->with('category')
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews as approved_reviews_count')
            ->when(! $isAdmin, fn ($query) => $query->published())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('short_description', 'like', '%'.$search.'%')
                        ->orWhere('moodle_shortname', 'like', '%'.$search.'%')
                        ->orWhere('instructor_name', 'like', '%'.$search.'%')
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->where('status', 'active')
            ->withCount(['courses' => fn ($query) => $query->published()])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $orders = collect();
        $students = collect();

        if ($isAdmin && $search !== '') {
            $orders = Order::query()
                ->with(['user', 'payment', 'items.course'])
                ->where(function ($query) use ($search): void {
                    $query->where('order_no', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('items.course', fn ($course) => $course->where('title', 'like', '%'.$search.'%'));
                })
                ->latest()
                ->limit(5)
                ->get();

            $students = User::query()
                ->whereHas('roles', fn ($role) => $role->where('name', 'Student'))
                ->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('moodle_user_id', $search);
                })
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('search.index', [
            'search' => $search,
            'courses' => $courses,
            'categories' => $categories,
            'orders' => $orders,
            'students' => $students,
            'isAdmin' => $isAdmin,
        ]);
    }
}
