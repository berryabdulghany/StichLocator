@props(['size' => 'md', 'href' => null])

@php
    $iconBox = $size === 'lg' ? 'h-12 w-12 text-2xl' : 'h-8 w-8 text-lg';
    $text = $size === 'lg' ? 'text-2xl' : 'text-lg';
@endphp

<a href="{{ $href ?? route('dashboard') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="{{ $iconBox }} inline-flex items-center justify-center rounded-lg bg-navy-700 text-white">
        <i class="ti ti-needle-thread" aria-hidden="true"></i>
    </span>
    <span class="{{ $text }} font-bold tracking-tight text-navy-700">{{ config('app.name') }}</span>
</a>
