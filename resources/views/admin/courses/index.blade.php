@extends('layouts.admin')

@section('page-title', 'کورسز منیجمنٹ')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">تمام کورسز</h3>
            <form action="{{ route('admin.courses.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg">🔄 Moodle سے سنک کریں</button>
            </form>
        </div>
    </div>

    <!-- Courses Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">کورس نام</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">قیمت</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">طلباء</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">حالت</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse(isset($courses) ? $courses : [] as $course)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $course->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $course->price ?? '—' }} PKR</td>
                        <td class="px-6 py-4 text-sm">{{ $course->enrollments_count ?? 0 }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-3 py-1 rounded-full text-white text-xs {{ $course->status === 'published' ? 'bg-green-500' : 'bg-yellow-500' }}">{{ ucfirst($course->status ?? 'Draft') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">کوئی کورسز نہیں ملے</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection