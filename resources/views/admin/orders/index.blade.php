@extends('layouts.admin')

@section('page-title', 'آرڈرز منیجمنٹ')

@section('content')
<div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <form method="GET" class="flex gap-4 flex-wrap">
            <input type="text" name="search" placeholder="آرڈر یا طالب علم تلاش کریں" class="flex-1 px-4 py-2 border rounded-lg" value="{{ request('search') }}">
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">تمام حالتیں</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>زیرالتوا</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مکمل</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">تلاش کریں</button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">آرڈر ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">طالب علم</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">رقم</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">حالت</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">تاریخ</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse(isset($orders) ? $orders : [] as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">#{{ $order->id ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $order->user->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $order->total ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-3 py-1 rounded-full text-white text-xs {{ $order->status === 'completed' ? 'bg-green-500' : 'bg-yellow-500' }}">{{ ucfirst($order->status ?? 'Pending') }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $order->created_at->format('M d, Y') ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">کوئی آرڈرز نہیں ملے</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection