<nav class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-8 sticky top-0 z-40">

    <!-- Left Side -->
    <div>
        <h2 class="text-lg font-semibold text-gray-800">
            Attendance Management System
        </h2>
    </div>

    <!-- Right Side -->
    <div class="flex items-center gap-4">

        <div class="text-right">
            <div class="font-medium text-gray-900">
                {{ Auth::user()->name }}
            </div>

            <div class="text-sm text-gray-500">
                {{ ucfirst(Auth::user()->role) }}
            </div>
        </div>

        <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
            >
                Logout
            </button>
        </form>

    </div>

</nav>