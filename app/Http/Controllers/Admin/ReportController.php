<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        // Reports mein paid revenue, pending orders aur failed Moodle enrolments ka summary banta hai.
        $paidPayments = Payment::where('status', 'paid');
        $pendingOrders = Order::whereIn('status', ['pending', 'pending_verification'])->count();
        $failedEnrollments = Enrollment::where('status', 'failed')->count();

        $coursePerformance = Course::withCount([
            'enrollments',
            'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
        ])->orderByDesc('active_enrollments_count')->take(10)->get();

        return view('admin.reports.index', [
            'totalStudents' => User::whereHas('roles', fn ($query) => $query->where('name', 'Student'))->count(),
            'totalRevenue' => (clone $paidPayments)->sum('amount'),
            'paidPayments' => (clone $paidPayments)->count(),
            'pendingOrders' => $pendingOrders,
            'failedEnrollments' => $failedEnrollments,
            'coursePerformance' => $coursePerformance,
        ]);
    }
}
