<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationCampaign;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = NotificationCampaign::query()
            ->with('creator')
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhereHas('creator', fn ($creator) => $creator
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'paused', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.notifications.index', [
            'campaigns' => $campaigns,
            'recentNotifications' => UserNotification::query()->with('user')->latest()->take(8)->get(),
            'types' => $this->types(),
            'audiences' => $this->audiences(),
            'triggers' => $this->triggers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        NotificationCampaign::create($this->validated($request) + [
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Notification rule created successfully.');
    }

    public function edit(NotificationCampaign $notificationCampaign): View
    {
        return view('admin.notifications.edit', [
            'campaign' => $notificationCampaign,
            'types' => $this->types(),
            'audiences' => $this->audiences(),
            'triggers' => $this->triggers(),
        ]);
    }

    public function update(Request $request, NotificationCampaign $notificationCampaign): RedirectResponse
    {
        $notificationCampaign->update($this->validated($request));

        return redirect()->route('admin.notifications.index')->with('status', 'Notification rule updated successfully.');
    }

    public function toggle(NotificationCampaign $notificationCampaign): RedirectResponse
    {
        $notificationCampaign->update(['is_active' => !$notificationCampaign->is_active]);

        return back()->with('status', 'Notification rule status updated.');
    }

    public function destroy(NotificationCampaign $notificationCampaign): RedirectResponse
    {
        $notificationCampaign->delete();

        return back()->with('status', 'Notification rule deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return [
            'info' => 'Information',
            'success' => 'Success',
            'warning' => 'Warning',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function audiences(): array
    {
        return [
            'students' => 'Students',
            'admins' => 'Admins',
            'instructors' => 'Instructors',
            'all' => 'Everyone',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function triggers(): array
    {
        return [
            'login' => 'When user logs in',
            'registration' => 'When new account is created',
            'purchase_success' => 'After paid course access',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:1200'],
            'type' => ['nullable', 'string', 'in:info,success,warning,urgent'],
            'audience' => ['nullable', 'string', 'in:students,admins,instructors,all'],
            'trigger_event' => ['nullable', 'string', 'in:login,registration,purchase_success'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'deliver_once' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['deliver_once'] = $request->boolean('deliver_once');
        $data['title'] = filled($data['title'] ?? null) ? $data['title'] : 'New academy update';
        $data['message'] = filled($data['message'] ?? null)
            ? $data['message']
            : 'Please check your dashboard for the latest course and account updates.';
        $data['type'] = filled($data['type'] ?? null) ? $data['type'] : 'info';
        $data['audience'] = filled($data['audience'] ?? null) ? $data['audience'] : 'students';
        $data['trigger_event'] = filled($data['trigger_event'] ?? null) ? $data['trigger_event'] : 'login';

        return $data;
    }
}
