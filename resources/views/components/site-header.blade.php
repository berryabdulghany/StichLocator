<header class="relative z-[1200] border-b border-stone-200 bg-white">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-2.5 lg:flex-nowrap lg:px-6">
        <x-logo />

        {{-- Slot pencarian (hanya di halaman peta). Di layar kecil pindah ke baris kedua. --}}
        @if (trim($slot))
            <div class="order-last w-full lg:order-none lg:mx-auto lg:w-auto lg:max-w-xl lg:flex-1">
                {{ $slot }}
            </div>
        @endif

        <div class="ml-auto flex items-center gap-2 lg:ml-0">
            <x-lang-switch />
            <x-account-menu />
        </div>
    </div>
</header>
