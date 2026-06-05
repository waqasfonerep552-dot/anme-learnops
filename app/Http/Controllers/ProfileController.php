<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load([
            'orders.payment',
            'orders.items.course',
            'payments',
            'enrollments.course',
            'progress.course',
            'courseReviews.course',
        ]);

        $activityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(12)
            ->get();

        $loginActivity = $activityLogs
            ->filter(fn (ActivityLog $activity): bool => in_array($activity->action, ['auth.login', 'auth.social_login', 'auth.registered', 'auth.logout'], true))
            ->take(6)
            ->values();

        $progressRows = $user->progress;
        $paidOrders = $user->orders->where('status', 'paid');
        $pendingOrders = $user->orders->where('status', 'pending');

        return view('profile.edit', [
            'user' => $user,
            'activityLogs' => $activityLogs,
            'loginActivity' => $loginActivity,
            'profileStats' => [
                'orders' => $user->orders->count(),
                'paid_orders' => $paidOrders->count(),
                'pending_orders' => $pendingOrders->count(),
                'active_courses' => $user->enrollments->where('status', 'active')->count(),
                'total_courses' => $user->enrollments->count(),
                'average_progress' => $progressRows->count() ? round((float) $progressRows->avg('progress'), 1) : 0,
                'total_spend' => $paidOrders->sum(fn ($order) => (float) $order->amount),
                'reviews' => $user->courseReviews->count(),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = filled($data['name'] ?? null) ? $data['name'] : $request->user()->name;

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            // Email change ho to verification dobara required hoti hai.
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Account delete se pehle current password verify karna safety ke liye zaroori hai.
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
