@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Revenue -->
        <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">کل رقم</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ isset($totalRevenue) ? $totalRevenue : '—' }}</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">کل آرڈرز</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ isset($totalOrders) ? $totalOrders : '—' }}</p>
                </div>
                <div class="text-4xl">📦</div>
            </div>
        </div>

        <!-- Total Students -->
        <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">کل طلباء</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ isset($totalStudents) ? $totalStudents : '—' }}</p>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <!-- Active Courses -->
        <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">فعال کورسز</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ isset($activeCourses) ? $activeCourses : '—' }}</p>
                </div>
                <div class="text-4xl">📚</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold text-gray-800 mb-4">فوری اقدامات</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.courses.sync') }}" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg text-center transition">
                <div class="text-2xl mb-2">🔄</div>
                <p class="text-sm font-medium text-gray-700">کورسز سنک کریں</p>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="p-4 bg-green-50 hover:bg-green-100 rounded-lg text-center transition">
                <div class="text-2xl mb-2">📋</div>
                <p class="text-sm font-medium text-gray-700">آرڈرز دیکھیں</p>
            </a>
            <a href="{{ route('admin.students.index') }}" class="p-4 bg-purple-50 hover:bg-purple-100 rounded-lg text-center transition">
                <div class="text-2xl mb-2">👤</div>
                <p class="text-sm font-medium text-gray-700">طلباء دیکھیں</p>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg text-center transition">
                <div class="text-2xl mb-2">⚙️</div>
                <p class="text-sm font-medium text-gray-700">سیٹنگز</p>
            </a>
        </div>
    </div>
</div>
@endsection