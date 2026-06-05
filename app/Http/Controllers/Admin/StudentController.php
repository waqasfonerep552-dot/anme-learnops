<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        // Student list role-based hai, search email/phone/Moodle ID tak support karti hai.
        $students = User::whereHas('roles', fn ($role) => $role->where('name', 'Student'))
            ->withCount(['orders', 'enrollments'])
            ->when($request->query('access_status'), fn ($query, $status) => $status === 'none'
                ? $query->doesntHave('enrollments')
                : $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('status', $status)))
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('moodle_user_id', $search);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', ['students' => $students]);
    }
}
