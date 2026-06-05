<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationCampaignDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()
                ->route('login')
                ->with('status', 'Google login credentials are not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(ActivityLogger $activity, NotificationCampaignDispatcher $notifications): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = Str::lower((string) $googleUser->getEmail());

            if ($email === '') {
                return redirect()
                    ->route('login')
                    ->with('status', 'Google account email is required for login.');
            }

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if ($user && $user->status !== 'active') {
                return redirect()
                    ->route('login')
                    ->with('status', 'Your account is not active. Please contact support.');
            }

            $newlyCreated = false;

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(40)),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                    'status' => 'active',
                ]);

                Role::findOrCreate('Student');
                $user->assignRole('Student');
                $newlyCreated = true;
            } else {
                $user->forceFill([
                    'google_id' => $user->google_id ?: $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'auth_provider' => 'google',
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();

                if (!$user->hasAnyRole(['Super Admin', 'Admin', 'Instructor', 'Student'])) {
                    Role::findOrCreate('Student');
                    $user->assignRole('Student');
                }
            }

            Auth::login($user, remember: true);

            $activity->log('auth.social_login', [
                'provider' => 'google',
                'email' => $user->email,
            ], $user, request());

            if ($newlyCreated) {
                $notifications->dispatchFor($user, 'registration', [
                    'auth_method' => 'google',
                ]);
            }

            $notifications->dispatchFor($user, 'login', [
                'auth_method' => 'google',
            ]);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->with('status', 'Google login failed. Please try again.');
        }
    }
}