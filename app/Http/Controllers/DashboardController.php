<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function student(): View
    {
        // Student dashboard ko ek hi query graph mein orders, enrolments, progress aur payments mil jate hain.
        $user = Auth::user()->load([
            'orders.items.course',
            'orders.payment',
            'enrollments.course',
            'progress.course',
            'payments',
            'notifications' => fn ($query) => $query->latest()->take(6),
        ]);

        return view('dashboard.student', [
            'user' => $user,
            'platformSettings' => PlatformSetting::publicValues([
                'notification_panel_title' => 'Latest updates',
            ]),
        ]);
    }

    public function admin(): View
    {
        // Extra safety: route middleware ke sath controller guard bhi admin access verify karta hai.
        abort_unless(Auth::user()?->isAdmin(), 403);

        $settings = PlatformSetting::publicValues([
            'admin_dashboard_period' => '90',
            'low_progress_threshold' => '35',
            'admin_analytics_enabled' => '1',
        ]);
        $period = in_array((int) ($settings['admin_dashboard_period'] ?? 90), [30, 90, 180, 365], true)
            ? (int) $settings['admin_dashboard_period']
            : 90;
        $lowProgressThreshold = max(0, min(100, (int) ($settings['low_progress_threshold'] ?? 35)));
        $analyticsEnabled = filter_var($settings['admin_analytics_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $periodStart = now()->subDays($period);

        $latestOrders = Order::latest()->with(['user', 'payment'])->take(10)->get();
        $paidPayments = Payment::where('status', 'paid');
        $paymentsForTrend = (clone $paidPayments)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['amount', 'paid_at', 'created_at']);
        $progressRows = CourseProgress::with('course:id,title,slug')->get();
        $orderStatuses = Order::query()->pluck('status')->countBy();
        $enrollmentStatuses = Enrollment::query()->pluck('status')->countBy();

        return view('dashboard.admin', [
            'students' => User::whereHas('roles', fn ($query) => $query->where('name', 'Student'))->count(),
            'revenue' => (clone $paidPayments)->sum('amount'),
            'periodRevenue' => (clone $paidPayments)->where('created_at', '>=', $periodStart)->sum('amount'),
            'orders' => $latestOrders,
            'totalOrders' => Order::count(),
            'paidOrders' => (int) ($orderStatuses['paid'] ?? 0),
            'pendingOrders' => (int) ($orderStatuses['pending'] ?? 0) + (int) ($orderStatuses['pending_verification'] ?? 0),
            'enrollments' => Enrollment::count(),
            'activeEnrollments' => (int) ($enrollmentStatuses['active'] ?? 0),
            'suspendedEnrollments' => (int) ($enrollmentStatuses['suspended'] ?? 0),
            'failedEnrollments' => (int) ($enrollmentStatuses['failed'] ?? 0),
            'courses' => Course::count(),
            'publicCourses' => Course::published()->count(),
            'pendingCourseApprovals' => Course::where('status', 'published')->where('is_admin_approved', false)->count(),
            'analyticsEnabled' => $analyticsEnabled,
            'dashboardPeriod' => $period,
            'lowProgressThreshold' => $lowProgressThreshold,
            'averageProgress' => $progressRows->count() ? round((float) $progressRows->avg('progress'), 1) : 0,
            'lowProgressLearners' => $progressRows->filter(fn (CourseProgress $row): bool => (float) $row->progress < $lowProgressThreshold)->count(),
            'progressBuckets' => $this->progressBuckets($progressRows),
            'revenueTrend' => $this->revenueTrend($paymentsForTrend),
            'topProgressCourses' => $this->topProgressCourses($progressRows),
            'orderStatusBreakdown' => $this->statusBreakdown($orderStatuses, [
                'paid' => 'Paid',
                'pending' => 'Pending',
                'pending_verification' => 'Pending verification',
                'cancelled' => 'Cancelled',
            ]),
            'enrollmentStatusBreakdown' => $this->statusBreakdown($enrollmentStatuses, [
                'active' => 'Active',
                'pending' => 'Pending',
                'setup_required' => 'Setup required',
                'failed' => 'Failed',
                'suspended' => 'Suspended',
            ]),
        ]);
    }

    private function progressBuckets($progressRows): array
    {
        $ranges = [
            ['label' => '0-25%', 'min' => 0, 'max' => 25, 'tone' => 'red'],
            ['label' => '26-50%', 'min' => 26, 'max' => 50, 'tone' => 'orange'],
            ['label' => '51-75%', 'min' => 51, 'max' => 75, 'tone' => 'blue'],
            ['label' => '76-99%', 'min' => 76, 'max' => 99, 'tone' => 'indigo'],
            ['label' => '100%', 'min' => 100, 'max' => 100, 'tone' => 'green'],
        ];
        $total = max(1, $progressRows->count());

        return collect($ranges)->map(function (array $range) use ($progressRows, $total): array {
            $count = $progressRows->filter(function (CourseProgress $row) use ($range): bool {
                $progress = (float) $row->progress;

                return $progress >= $range['min'] && $progress <= $range['max'];
            })->count();

            return $range + [
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        })->all();
    }

    private function revenueTrend($payments): array
    {
        $months = collect(range(5, 0))->map(fn (int $monthsBack): Carbon => now()->subMonths($monthsBack)->startOfMonth());
        $max = max(1, (float) $payments->sum('amount'));

        $items = $months->map(function (Carbon $month) use ($payments): array {
            $value = (float) $payments->filter(function (Payment $payment) use ($month): bool {
                $date = $payment->paid_at ?: $payment->created_at;

                return $date && $date->isSameMonth($month);
            })->sum('amount');

            return [
                'label' => $month->format('M'),
                'value' => $value,
            ];
        });
        $monthMax = max(1, (float) $items->max('value'), $max / 6);

        return $items->map(fn (array $item): array => $item + [
            'height' => max(8, round(($item['value'] / $monthMax) * 100, 1)),
        ])->all();
    }

    private function topProgressCourses($progressRows): array
    {
        return $progressRows
            ->filter(fn (CourseProgress $row): bool => $row->course !== null)
            ->groupBy('course_id')
            ->map(function ($rows): array {
                $course = $rows->first()->course;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'learners' => $rows->count(),
                    'average' => round((float) $rows->avg('progress'), 1),
                ];
            })
            ->sortByDesc('average')
            ->take(5)
            ->values()
            ->all();
    }

    private function statusBreakdown($statuses, array $labels): array
    {
        $total = max(1, (int) $statuses->sum());

        return collect($labels)->map(function (string $label, string $key) use ($statuses, $total): array {
            $count = (int) ($statuses[$key] ?? 0);

            return [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        })->values()->all();
    }
}
