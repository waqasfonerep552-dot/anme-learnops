<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PaymentService;
use App\Services\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class CheckoutController extends Controller
{
    public function show(Course $course): View
    {
        // Draft/unapproved/hidden courses checkout par available nahi honi chahiye.
        abort_unless($course->isPubliclyAvailable(), 404);

        return view('checkout.show', [
            'course' => $course,
            'platformSettings' => PlatformSetting::publicValues([
                'payment_gateway_label' => 'Easypaisa',
                'checkout_button_label' => 'Continue to payment',
                'payment_instructions' => 'After payment verification, ANME Academy access is activated automatically.',
                'student_phone_required' => '0',
            ]),
        ]);
    }

    public function store(Request $request, Course $course, PaymentService $payments, ActivityLogger $activity, SystemNotificationService $notifications): RedirectResponse
    {
        // Purchase sirf admin-approved public course ke liye allowed hai.
        abort_unless($course->isPubliclyAvailable(), 404);

        // Phone requirement admin setting se control hoti hai.
        $phoneRequired = PlatformSetting::boolean('student_phone_required', false);

        // Checkout se student account details aur password collect karte hain.
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => [$phoneRequired ? 'required' : 'nullable', 'string', 'max:30'],
            'password' => [Rule::requiredIf(! Auth::check()), 'nullable', 'string', 'min:8'],
        ]);

        // User, order, order item aur pending payment ek transaction mein bante hain.
        $order = DB::transaction(function () use ($data, $course, $payments): Order {
            Role::findOrCreate('Student');

            $email = strtolower($data['email']);
            $displayName = filled($data['name'] ?? null) ? trim($data['name']) : $this->nameFromEmail($email);

            if (Auth::check()) {
                $user = Auth::user();

                if ($user->email !== $email && User::where('email', $email)->whereKeyNot($user->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'This email is already used by another account.',
                    ]);
                }

                $user->update([
                    'name' => filled($data['name'] ?? null) ? $displayName : $user->name,
                    'email' => $email,
                    'phone' => $data['phone'] ?? $user->phone,
                ]);
            } else {
                $user = User::where('email', $email)->first();

                if ($user && !Hash::check((string) $data['password'], $user->password)) {
                    // Same email already hai to wrong password se duplicate purchase allow nahi karte.
                    throw ValidationException::withMessages([
                        'email' => 'This email already exists. Please use the correct password or login first.',
                    ]);
                }

                if (!$user) {
                    $user = User::create([
                        'name' => $displayName,
                        'email' => $email,
                        'phone' => $data['phone'] ?? null,
                        'password' => Hash::make((string) $data['password']),
                        'status' => 'active',
                    ]);
                } else {
                    $user->update([
                        'name' => filled($data['name'] ?? null) ? $displayName : $user->name,
                        'phone' => $data['phone'] ?? $user->phone,
                    ]);
                }
            }

            if (!$user->hasRole('Student')) {
                // Jo course purchase karta hai usko website side Student role milta hai.
                $user->assignRole('Student');
            }

            $this->ensureCourseCanBePurchased($user, $course);

            Auth::login($user);

            $order = Order::create([
                // Order pending rahega jab tak Easypaisa payment paid confirm na ho.
                'user_id' => $user->id,
                'order_no' => 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'amount' => $course->price,
                'currency' => $course->currency,
                'status' => 'pending',
            ]);

            $order->items()->create([
                'course_id' => $course->id,
                'price' => $course->price,
                'currency' => $course->currency,
            ]);

            $payments->createEasypaisaPayment($order);

            return $order->refresh();
        });

        $activity->log('checkout.order_created', [
            'order_no' => $order->order_no,
            'course_id' => $course->id,
            'course_title' => $course->title,
            'amount' => (float) $order->amount,
            'currency' => $order->currency,
        ], $order->user, $request);

        $notifications->notifyAdmins(
            'order.created',
            'New order started',
            "{$order->user->name} started checkout for {$course->title}. Payment receipt is still pending.",
            [
                'order_no' => $order->order_no,
                'course_id' => $course->id,
                'student_id' => $order->user_id,
                'amount' => (float) $order->amount,
                'currency' => $order->currency,
                'url' => route('orders.show', $order),
            ]
        );

        // Real gateway configured ho to redirect gateway par, warna local pending page par.
        return redirect($payments->checkoutUrl($order->payment));
    }

    private function nameFromEmail(string $email): string
    {
        $name = str($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->trim()->toString();

        return $name !== '' ? $name : 'ANME Student';
    }

    private function ensureCourseCanBePurchased(User $user, Course $course): void
    {
        $hasActiveOrPreparingAccess = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'setup_required', 'pending', 'suspended'])
            ->exists();

        if ($hasActiveOrPreparingAccess) {
            throw ValidationException::withMessages([
                'email' => 'You already have ANME Academy access or an access setup in progress for this course.',
            ]);
        }

        $hasOpenOrderForCourse = $user->orders()
            ->whereIn('status', ['pending', 'pending_verification', 'paid'])
            ->whereHas('items', fn ($items) => $items->where('course_id', $course->id))
            ->exists();

        if ($hasOpenOrderForCourse) {
            throw ValidationException::withMessages([
                'email' => 'You already have an open order for this course. Please check My Learning or your order page.',
            ]);
        }
    }
}
