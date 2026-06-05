<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AcademyAccessController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use Illuminate\Support\Facades\Route;

// Public business pages: yahan visitor courses dekhta aur checkout start karta hai.
Route::get('/', HomeController::class)->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/live', [SearchController::class, 'live'])->name('search.live');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{course:slug}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/courses/{course:slug}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Easypaisa gateway user ko payment ke baad isi return URL par wapas bhejta hai.
Route::get('/payments/easypaisa/return', [PaymentController::class, 'return'])->name('payments.easypaisa.return');

// Student area protected hai; order/payment/profile sirf logged-in user dekh sakta hai.
Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'student'])->name('dashboard');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
        Route::get('/payments/{payment}/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::post('/payments/{payment}/manual-proof', [PaymentController::class, 'submitManualProof'])->name('payments.manual-proof.store');
    Route::post('/courses/{course:slug}/reviews', [CourseReviewController::class, 'store'])->name('courses.reviews.store');
    Route::get('/academy-access/setup', [AcademyAccessController::class, 'edit'])->name('academy-access.edit');
    Route::patch('/academy-access/setup', [AcademyAccessController::class, 'update'])->name('academy-access.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin business panel: role middleware ensure karta hai ke normal student admin pages na khol sake.
Route::prefix('admin')
    ->middleware(['auth', 'role:Super Admin|Admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/retry-fulfillment', [AdminOrderController::class, 'retryFulfillment'])->name('orders.retry-fulfillment');
        Route::post('/orders/{order}/mark-paid-testing', [AdminOrderController::class, 'markPaidForTesting'])->name('orders.mark-paid-testing');
        Route::post('/orders/{order}/approve-manual-payment', [AdminOrderController::class, 'approveManualPayment'])->name('orders.approve-manual-payment');
        Route::post('/orders/{order}/reject-manual-payment', [AdminOrderController::class, 'rejectManualPayment'])->name('orders.reject-manual-payment');
        Route::post('/orders/{order}/suspend-access', [AdminOrderController::class, 'suspendAccess'])->name('orders.suspend-access');
        Route::post('/orders/{order}/reactivate-access', [AdminOrderController::class, 'reactivateAccess'])->name('orders.reactivate-access');
        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses/sync', [AdminCourseController::class, 'sync'])->name('courses.sync');
        Route::post('/courses/{course}/approve-public', [AdminCourseController::class, 'approvePublic'])->name('courses.approve-public');
        Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::patch('/courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{courseReview}', [AdminReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{courseReview}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
        Route::get('/notifications/{notificationCampaign}/edit', [AdminNotificationController::class, 'edit'])->name('notifications.edit');
        Route::patch('/notifications/{notificationCampaign}', [AdminNotificationController::class, 'update'])->name('notifications.update');
        Route::patch('/notifications/{notificationCampaign}/toggle', [AdminNotificationController::class, 'toggle'])->name('notifications.toggle');
        Route::delete('/notifications/{notificationCampaign}', [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/production-readiness', [AdminSettingController::class, 'productionReadiness'])->name('settings.production-readiness');
        Route::get('/settings/moodle-diagnostics', [AdminSettingController::class, 'moodleDiagnostics'])->name('settings.moodle-diagnostics');
        Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity.index');
    });

// Breeze auth routes login/register/password pages provide karte hain.
require __DIR__.'/auth.php';
