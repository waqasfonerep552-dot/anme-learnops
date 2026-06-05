<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationCampaignDispatcher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, ActivityLogger $activity, NotificationCampaignDispatcher $notifications): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => filled($request->name) ? trim($request->name) : $this->nameFromEmail($request->email),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Role::findOrCreate('Student');
        $user->assignRole('Student');

        event(new Registered($user));

        Auth::login($user);

        $activity->log('auth.registered', [
            'email' => $user->email,
            'auth_method' => 'password',
            'assigned_role' => 'Student',
        ], $user, $request);

        $notifications->dispatchFor($user, 'registration', [
            'auth_method' => 'password',
        ]);

        $notifications->dispatchFor($user, 'login', [
            'auth_method' => 'password',
        ]);

        return redirect(route('dashboard', absolute: false));
    }

    private function nameFromEmail(string $email): string
    {
        $name = str($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->trim()->toString();

        return $name !== '' ? $name : 'ANME Student';
    }
}
