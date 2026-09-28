<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>StitchLocator</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-x-hidden">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            @include('components.navbar')
            
            <!-- Content -->
            <main class="flex-1 bg-gray-100 relative">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Escape teks sebelum dimasukkan ke innerHTML / popup (mencegah XSS)
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
    @stack('scripts')

    <!-- Profile Modal -->
    <div id="profileModal" class="absolute top-16 right-4 z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-auto relative text-center border border-gray-200">
            <button onclick="closeProfileModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
            @auth
                <p class="text-sm text-gray-500 mb-2">{{ Auth::user()->email }}</p>
                <div class="mb-4">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="Profile Picture" class="w-20 h-20 rounded-full object-cover mx-auto border-2 border-blue-500">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mx-auto border-2 border-blue-500">
                            <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                        </div>
                    @endif
                </div>
                <h2 class="text-lg font-bold text-gray-800 mb-4">Halo, {{ Auth::user()->name }}!</h2>
                <a href="{{ route('user.profile') }}" class="block w-full bg-blue-100 text-blue-700 py-2 rounded-lg hover:bg-blue-200 text-center mb-2 text-sm">Kelola Akun Anda</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 text-sm">Logout</button>
                </form>
            @else
                <p class="text-gray-700 mb-4">Anda belum login.</p>
                <a href="{{ route('login') }}" class="block w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 text-center">Login</a>
            @endauth
        </div>
    </div>

    <script>
        function openProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const profileButton = document.getElementById('profileButton');
            if (profileButton) {
                profileButton.addEventListener('click', openProfileModal);
            }
        });
    </script>
</body>
</html>
