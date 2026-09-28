<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="bg-white">
    <div class="flex h-screen overflow-x-hidden">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            @include('components.navbar')

            <!-- Content -->
            <main class="flex-1 bg-stone-50 relative">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')

    <!-- Profile Modal -->
    <div id="profileModal" class="absolute top-16 right-4 z-50 hidden">
        <div class="floating relative mx-auto w-72 p-6 text-center">
            <button onclick="closeProfileModal()" class="absolute right-2 top-2 text-stone-400 hover:text-stone-700" aria-label="{{ __('Close') }}">
                <i class="ti ti-x text-lg" aria-hidden="true"></i>
            </button>
            @auth
                <p class="mb-3 text-sm text-stone-500">{{ Auth::user()->email }}</p>
                <div class="mb-3">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ __('Profile picture') }}" class="mx-auto h-20 w-20 rounded-full border-2 border-navy-600 object-cover">
                    @else
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-navy-600 bg-navy-50 text-navy-700">
                            <i class="ti ti-user text-4xl" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
                <h2 class="mb-4 text-lg font-bold text-stone-800">{{ __('Hi, :name!', ['name' => Auth::user()->name]) }}</h2>
                <a href="{{ route('user.profile') }}" class="btn-outline mb-2 w-full">
                    <i class="ti ti-settings" aria-hidden="true"></i>{{ __('Manage your account') }}
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-ghost w-full text-red-600 hover:bg-red-50">
                        <i class="ti ti-logout" aria-hidden="true"></i>{{ __('Log out') }}
                    </button>
                </form>
            @else
                <p class="mb-4 text-stone-600">{{ __('You are not logged in.') }}</p>
                <a href="{{ route('login') }}" class="btn-primary w-full">{{ __('Log in') }}</a>
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
