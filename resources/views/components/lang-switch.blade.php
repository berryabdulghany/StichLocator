{{-- Tombol ganti bahasa: ID | EN --}}
<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border border-stone-300 bg-white p-0.5 text-xs font-semibold']) }}
     role="group" aria-label="{{ __('Language') }}">
    @foreach (config('app.supported_locales') as $locale)
        <a href="{{ route('locale.switch', $locale) }}"
           hreflang="{{ $locale }}"
           @class([
               'rounded-full px-2.5 py-1 uppercase transition',
               'bg-navy-700 text-white' => app()->getLocale() === $locale,
               'text-stone-500 hover:text-stone-800' => app()->getLocale() !== $locale,
           ])
           @if (app()->getLocale() === $locale) aria-current="true" @endif>
            {{ $locale }}
        </a>
    @endforeach
</div>
