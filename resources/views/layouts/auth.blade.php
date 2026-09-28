<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-stone-50">
    <div class="flex items-center justify-between px-4 py-4 md:px-8">
        <x-logo />
        <x-lang-switch />
    </div>

    <main class="flex items-center justify-center px-4 pb-12 pt-4 md:pt-12">
        <div class="card-stitch w-full max-w-md p-8">
            @yield('content')
        </div>
    </main>

    <script>
        // Tampilkan / sembunyikan password
        function togglePasswordVisibility(button) {
            const input = document.getElementById(button.dataset.target);
            const icon = button.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('ti-eye', !show);
            icon.classList.toggle('ti-eye-off', show);
        }
    </script>
</body>
</html>
