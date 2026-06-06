@extends('layouts.admin')

@section('page-title', 'طلباء منیجمنٹ')

@section('content')
<div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <form method="GET" class="flex gap-4 flex-wrap">
            <input type="text" name="search" placeholder="نام یا ای میل تلاش کریں" class="flex-1 px-4 py-2 border rounded-lg" value="{{ request('search') }}">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">تلاش کریں</button>
        </form>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">نام</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">ای میل</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">کورسز</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">شامل ہوا</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse(isset($students) ? $students : [] as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $student->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $student->email ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $student->enrollments_count ?? 0 }}</td>
                        <td class="px-6 py-4 text-sm">{{ $student->created_at->format('M d, Y') ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">کوئی طلباء نہیں ملے</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection