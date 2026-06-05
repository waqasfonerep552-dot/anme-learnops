<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\NotificationCampaignDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogger $activity, NotificationCampaignDispatcher $notifications): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $activity->log('auth.login', [
            'email' => $request->string('email')->toString(),
            'remember' => $request->boolean('remember'),
            'auth_method' => 'password',
        ], $request->user(), $request);

        $notifications->dispatchFor($request->user(), 'login', [
            'auth_method' => 'password',
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $activity->log('auth.logout', [
            'auth_method' => 'password',
        ], $request->user(), $request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}