{{--
    Grafik batang harian sederhana (tanpa library): satu seri, satu sumbu, tooltip saat hover,
    dan tabel tersembunyi untuk pembaca layar.
    Contoh: <x-daily-bars :days="$timeline" caption="Ulasan per hari" unit=":count review|:count reviews" />
    $days: koleksi ['label' => '1 Okt', 'value' => 3]
--}}
@props(['days', 'caption', 'unit', 'bar' => 'bg-navy-600 group-hover:bg-navy-800'])

@php($max = max(1, $days->max('value')))

<div {{ $attributes }}>
    <div class="flex gap-2" aria-hidden="true">
        {{-- Sumbu Y: hanya nilai maksimum dan nol --}}
        <div class="flex h-48 flex-col justify-between text-right text-[11px] text-stone-400">
            <span>{{ $max }}</span>
            <span>0</span>
        </div>
        <div class="relative flex-1">
            <div class="absolute inset-x-0 top-0 border-t border-dashed border-stone-200"></div>
            <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-stone-200"></div>
            <div class="relative flex h-48 items-end gap-[2px] border-b border-stone-300">
                @foreach ($days as $day)
                    <div class="group relative flex h-full flex-1 items-end justify-center">
                        <div class="w-full max-w-[14px] rounded-t transition {{ $bar }}"
                             style="height: {{ $day['value'] ? max(4, $day['value'] / $max * 100) : 0 }}%"></div>
                        <div class="pointer-events-none absolute bottom-full z-10 mb-2 hidden whitespace-nowrap rounded-lg bg-stone-900 px-2 py-1 text-xs text-white shadow-lg group-hover:block">
                            {{ $day['label'] }}: <b class="font-semibold">{{ trans_choice($unit, $day['value'], ['count' => $day['value']]) }}</b>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex justify-between text-[11px] text-stone-400">
                <span>{{ $days->first()['label'] }}</span>
                <span>{{ $days[intdiv($days->count(), 2)]['label'] ?? '' }}</span>
                <span>{{ $days->last()['label'] }}</span>
            </div>
        </div>
    </div>

    <table class="sr-only">
        <caption>{{ $caption }}</caption>
        <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Total') }}</th></tr></thead>
        <tbody>
            @foreach ($days as $day)
                <tr><td>{{ $day['label'] }}</td><td>{{ $day['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
