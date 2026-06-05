<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserNotification;

class SystemNotificationService
{
    public function notifyAdmins(string $type, string $title, string $message, array $data = []): int
    {
        // Admin action alerts hamesha create hote hain, taake order/payment review miss na ho.
        $created = 0;

        User::whereHas('roles', fn ($role) => $role->whereIn('name', ['Super Admin', 'Admin']))
            ->get()
            ->each(function (User $admin) use ($type, $title, $message, $data, &$created): void {
                UserNotification::create([
                    'user_id' => $admin->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);

                $created++;
            });

        return $created;
    }

    public function notifyUser(User $user, string $type, string $title, string $message, array $data = []): ?UserNotification
    {
        // Student notification admin setting se control hoti hai.
        if (! PlatformSetting::boolean('student_notifications_enabled', true)) {
            return null;
        }

        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
