<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        // Admin ko latest business/Moodle events trace karne ke liye activity log milta hai.
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->query('q'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhere('country_code', 'like', '%'.$search.'%')
                        ->orWhere('country_name', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->query('action'), fn ($query, $action) => $query->where('action', $action))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
