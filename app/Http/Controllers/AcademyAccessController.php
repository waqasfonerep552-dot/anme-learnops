<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AcademyFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class AcademyAccessController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user()->load([
            'orders.items.course',
            'orders.enrollments.course',
        ]);

        $paidOrders = $user->orders
            ->where('status', 'paid')
            ->values();

        if ($this->academyAccessAlreadyConfigured($user)) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'ANME Academy login already configured hai. Agar password reset chahiye to support/admin se contact karein.');
        }

        return view('academy-access.edit', [
            'user' => $user,
            'paidOrders' => $paidOrders,
            'defaultUsername' => old('academy_username', $user->academyUsername()),
        ]);
    }

    public function update(Request $request, AcademyFulfillmentService $academy): RedirectResponse
    {
        $user = Auth::user()->load('orders.enrollments');

        if ($this->academyAccessAlreadyConfigured($user)) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'ANME Academy login already configured hai. Duplicate setup allowed nahi hai.');
        }

        $username = str((string) $request->input('academy_username'))
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]/', '_')
            ->trim('._-')
            ->toString();

        $request->merge(['academy_username' => $username]);

        $data = $request->validate([
            'academy_username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'academy_username')->ignore($user->id),
            ],
            'academy_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'academy_username.regex' => 'Username mein sirf lowercase letters, numbers, dot, dash aur underscore allowed hain.',
            'academy_password.symbols' => 'Academy password mein kam az kam 1 special character hona zaroori hai, jaise !, @, # ya *.',
        ]);

        $paidOrders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->with(['user', 'items.course', 'payment'])
            ->get();

        if ($paidOrders->isEmpty()) {
            return back()->with('error', 'Academy access setup ke liye pehle approved paid order chahiye.');
        }

        $user->forceFill([
            'academy_username' => $data['academy_username'],
        ])->save();

        try {
            foreach ($paidOrders as $order) {
                $academy->fulfillWithPassword($order, $data['academy_password']);
            }
        } catch (Throwable $exception) {
            return back()
                ->withInput(['academy_username' => $data['academy_username']])
                ->with('error', 'Academy access activate nahi ho saki: '.$exception->getMessage());
        }

        return redirect()->route('dashboard')->with('status', 'ANME Academy login set ho gaya. Course access active kar di gayi hai.');
    }

    private function academyAccessAlreadyConfigured($user): bool
    {
        $hasSetupRequiredEnrollment = $user->orders
            ->flatMap(fn ($order) => $order->enrollments)
            ->contains('status', 'setup_required');

        return $user->hasAcademyPassword()
            && filled($user->moodle_user_id)
            && blank($user->academy_setup_required_at)
            && ! $hasSetupRequiredEnrollment;
    }
}
