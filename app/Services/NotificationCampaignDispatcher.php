<?php

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserNotification;

class NotificationCampaignDispatcher
{
    public function dispatchFor(User $user, string $trigger, array $context = []): int
    {
        if (!PlatformSetting::boolean('student_notifications_enabled', true)) {
            return 0;
        }

        $created = 0;

        NotificationCampaign::query()
            ->activeForTrigger($trigger)
            ->get()
            ->each(function (NotificationCampaign $campaign) use ($user, $trigger, $context, &$created): void {
                if (!$this->audienceMatches($user, $campaign->audience)) {
                    return;
                }

                if ($campaign->deliver_once && $this->alreadyDelivered($user, $campaign)) {
                    return;
                }

                UserNotification::create([
                    'user_id' => $user->id,
                    'notification_campaign_id' => $campaign->id,
                    'type' => $campaign->type,
                    'title' => $this->replaceTokens($campaign->title, $user, $context),
                    'message' => $this->replaceTokens($campaign->message, $user, $context),
                    'data' => array_merge($context, [
                        'campaign_id' => $campaign->id,
                        'trigger_event' => $trigger,
                    ]),
                ]);

                $created++;
            });

        return $created;
    }

    private function alreadyDelivered(User $user, NotificationCampaign $campaign): bool
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->where('notification_campaign_id', $campaign->id)
            ->exists();
    }

    private function audienceMatches(User $user, string $audience): bool
    {
        return match ($audience) {
            'all' => true,
            'students' => $user->hasRole('Student'),
            'admins' => $user->hasAnyRole(['Super Admin', 'Admin']),
            'instructors' => $user->hasRole('Instructor'),
            default => false,
        };
    }

    private function replaceTokens(string $text, User $user, array $context): string
    {
        $tokens = [
            '{name}' => $user->name,
            '{email}' => $user->email,
            '{date}' => now()->format('d M Y'),
            '{course}' => (string) ($context['course_title'] ?? 'your course'),
            '{order}' => (string) ($context['order_no'] ?? 'your order'),
        ];

        return strtr($text, $tokens);
    }
}