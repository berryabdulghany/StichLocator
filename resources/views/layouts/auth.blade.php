{{-- Layout masuk/daftar: form di kiri, panel foto + kutipan ulasan di kanan (desktop) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Kolom form --}}
        <div class="flex flex-col px-4 py-5 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between">
                <x-logo />
                <x-lang-switch />
            </div>

            <main class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-sm">
                    @yield('content')
                </div>
            </main>

            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 self-start text-sm text-stone-500 hover:text-navy-700">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>{{ __('Back to home') }}
            </a>
        </div>

        {{-- Panel visual (desktop) --}}
        <aside class="relative hidden overflow-hidden bg-navy-800 lg:block" aria-hidden="true">
            <img src="{{ asset('images/penjahit/mengukur-kain.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-t from-navy-950/90 via-navy-900/40 to-navy-900/10"></div>
            {{-- Bingkai jahitan --}}
            <div class="absolute inset-6 rounded-3xl border-2 border-dashed border-white/30"></div>

            <div class="absolute inset-x-12 bottom-14 text-white">
                <p class="text-sm font-semibold uppercase tracking-wider text-terra-300">{{ config('app.name') }}</p>
                <p class="mt-2 max-w-md text-3xl font-bold leading-snug">{{ __('Neat stitches start with the right tailor.') }}</p>

                @if ($quote)
                    <figure class="mt-8 max-w-md rounded-2xl bg-white/10 p-5 backdrop-blur">
                        <p class="text-terra-300">{{ str_repeat('★', $quote->rating) }}</p>
                        <blockquote class="mt-2 text-base leading-relaxed">&ldquo;{{ $quote->review }}&rdquo;</blockquote>
                        <figcaption class="mt-3 text-sm text-navy-100">
                            {{ $quote->user->name }} · {{ __('about :tailor', ['tailor' => $quote->location->name]) }}
                        </figcaption>
                    </figure>
                @endif
            </div>
        </aside>
    </div>

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
