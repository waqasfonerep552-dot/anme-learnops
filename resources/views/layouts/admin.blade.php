<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --color-primary: #3b82f6;
            --color-danger: #ef4444;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-dark: #1f2937;
            --color-light: #f3f4f6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-lg">
            <div class="p-6 border-b border-blue-700">
                <h1 class="text-2xl font-bold">{{ config('app.name') }}</h1>
                <p class="text-blue-200 text-sm mt-1">Admin Dashboard</p>
            </div>

            <nav class="p-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    📊 Dashboard
                </a>
                <a href="{{ route('admin.orders.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.orders.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    📦 Orders
                </a>
                <a href="{{ route('admin.students.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.students.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    👥 Students
                </a>
                <a href="{{ route('admin.courses.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.courses.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    📚 Courses
                </a>
                <a href="{{ route('admin.reports.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    📈 Reports
                </a>
                <a href="{{ route('admin.reviews.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.reviews.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    ⭐ Reviews
                </a>
                <a href="{{ route('admin.notifications.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.notifications.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    🔔 Notifications
                </a>
                <a href="{{ route('admin.activity.index') }}" class="block px-4 py-3 rounded-lg {{ request()->routeIs('admin.activity.*') ? 'bg-blue-600' : 'hover:bg-blue-700' }} transition">
                    📝 Activity
                </a>

                <div class="border-t border-blue-700 pt-4 mt-4">
                    <button class="w-full text-left px-4 py-3 rounded-lg hover:bg-blue-700 transition collapsed group" data-bs-toggle="collapse" data-bs-target="#settingsMenu">
                        ⚙️ Settings
                    </button>
                    <div id="settingsMenu" class="collapse ml-4 space-y-1">
                        <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm text-blue-100 rounded hover:bg-blue-700 transition">
                            Platform Settings
                        </a>
                        <a href="{{ route('admin.settings.production-readiness') }}" class="block px-4 py-2 text-sm text-blue-100 rounded hover:bg-blue-700 transition">
                            Production Readiness
                        </a>
                        <a href="{{ route('admin.settings.moodle-diagnostics') }}" class="block px-4 py-2 text-sm text-blue-100 rounded hover:bg-blue-700 transition">
                            Moodle Diagnostics
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow-md border-b border-gray-200">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Admin Panel')</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Flash Messages -->
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <strong>Errors:</strong>
                        <ul class="mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>