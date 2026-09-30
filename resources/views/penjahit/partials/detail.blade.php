{{--
    Detail penjahit bergaya "nota jahit".
    Dipakai di drawer halaman peta (dimuat via AJAX) dan di halaman penuh /penjahit/{slug}.
--}}
@php
    use App\Support\Format;

    $photos = $location->photos;
    $status = $location->openingStatus();
    $week = $location->weeklyHours();
    $today = now(\App\Models\Location::TIMEZONE)->dayOfWeek;
    $reviewCount = $reviews->count();
    $average = $reviewCount ? round($reviews->avg('rating'), 1) : null;
    $distribution = collect(range(5, 1))->mapWithKeys(fn ($star) => [$star => $reviews->where('rating', $star)->count()]);
    $mondayFirst = [1, 2, 3, 4, 5, 6, 0];
@endphp

<article class="detail" data-detail data-location-id="{{ $location->id }}" data-share-url="{{ route('penjahit.show', $location->slug) }}"
         data-share-title="{{ $location->name }}">

    @unless ($location->is_published)
        {{-- Hanya admin & mitra pemiliknya yang bisa melihat draf (lihat PenjahitController@show) --}}
        <div class="flex items-center gap-2 bg-amber-50 px-4 py-2.5 text-sm text-amber-900" role="note">
            <i class="ti ti-eye-off" aria-hidden="true"></i>
            <span>{{ __('Draft preview: this tailor is not visible to visitors yet.') }}</span>
            <a href="{{ ($isOwner ?? false) ? route('mitra.profile.edit') : route('admin.tailors.edit', $location) }}" class="ml-auto font-semibold underline">{{ __('Edit') }}</a>
        </div>
    @endunless

    @if ($location->isTemporarilyClosed())
        <div class="flex items-start gap-2 border-b border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900" role="note">
            <i class="ti ti-calendar-off mt-0.5 text-lg" aria-hidden="true"></i>
            <div>
                <p class="font-semibold">{{ __('Temporarily closed until :date', ['date' => $location->closed_until->translatedFormat('l, j F Y')]) }}</p>
                @if ($location->closure_note)
                    <p class="text-amber-800">{{ $location->closure_note }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Kolase foto --}}
    @if ($photos->isNotEmpty())
        <div class="grid h-52 grid-cols-3 grid-rows-2 gap-1">
            @foreach ($photos->take(3) as $i => $photo)
                <button type="button" data-lightbox-index="{{ $i }}"
                        @class([
                            'relative overflow-hidden bg-navy-50',
                            'col-span-3 row-span-2' => $i === 0 && $photos->count() === 1,
                            'col-span-2 row-span-2' => $i === 0 && $photos->count() > 1,
                            'row-span-2' => $i === 1 && $photos->count() === 2,
                        ])>
                    <img src="{{ $photo->url }}" alt="{{ __('Photo :number of :name', ['number' => $i + 1, 'name' => $location->name]) }}"
                         class="h-full w-full object-cover transition hover:scale-105" loading="lazy">
                    @if ($i === 2 && $photos->count() > 3)
                        <span class="absolute inset-0 flex items-center justify-center bg-stone-900/50 text-sm font-semibold text-white">
                            +{{ $photos->count() - 3 }} {{ __('photos') }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
        <template data-lightbox-photos>@json($photos->map(fn ($p) => ['url' => $p->url, 'credit' => $p->credit, 'source' => $p->source_url])->values())</template>
    @endif

    <div class="space-y-1 px-5 pt-4">
        <h2 class="text-xl font-bold text-stone-900">{{ $location->name }}</h2>
        <p class="flex items-start gap-1.5 text-sm text-stone-500">
            <i class="ti ti-map-pin mt-0.5" aria-hidden="true"></i>{{ $location->address }}
        </p>
        <div class="flex flex-wrap gap-1.5 pt-2">
            <span class="{{ $status['open'] ? 'badge-open' : 'badge-closed' }}">{{ $location->statusText() }}</span>
            @if ($average !== null)
                <span class="badge-tag">★ {{ number_format($average, 1) }} · {{ trans_choice(':count review|:count reviews', $reviewCount, ['count' => $reviewCount]) }}</span>
            @endif
            @if ($location->offers_home_visit)
                <span class="badge bg-navy-50 text-navy-700"><i class="ti ti-home-move" aria-hidden="true"></i>{{ __('Home visit for measuring') }}</span>
            @endif
        </div>
    </div>

    {{-- Aksi: WhatsApp sebagai tombol utama --}}
    <div class="flex gap-2 px-5 py-4">
        @if ($wa = $location->whatsappUrl($location->whatsappGreeting()))
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-accent flex-1"
               data-track="whatsapp" data-track-url="{{ route('penjahit.track', $location->id) }}">
                <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true"></i>{{ __('Chat on WhatsApp') }}
            </a>
        @endif
        {{-- Di halaman peta, klik ini menampilkan pratinjau rute; tanpa JS langsung membuka Google Maps --}}
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->lat }},{{ $location->lng }}" target="_blank" rel="noopener"
           data-route data-id="{{ $location->id }}" data-track="route" data-track-url="{{ route('penjahit.track', $location->id) }}"
           class="btn-icon" title="{{ __('Route') }}" aria-label="{{ __('Route') }}">
            <i class="ti ti-route" aria-hidden="true"></i>
        </a>
        <button type="button" data-save data-id="{{ $location->id }}" aria-pressed="false"
                class="btn-icon" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
            <i class="ti ti-bookmark" aria-hidden="true"></i>
        </button>
        <button type="button" data-share class="btn-icon" title="{{ __('Share') }}" aria-label="{{ __('Share') }}">
            <i class="ti ti-share" aria-hidden="true"></i>
        </button>
    </div>

    @if ($location->description)
        <p class="px-5 pb-4 text-sm leading-relaxed text-stone-600">{{ $location->description }}</p>
    @endif

    {{-- Nota jahit: layanan & harga --}}
    @if ($location->services->isNotEmpty())
        <section class="border-t border-stone-100 bg-stone-50 px-5 py-4">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-stone-800">
                <i class="ti ti-receipt text-navy-700" aria-hidden="true"></i>{{ __('Services and prices') }}
            </h3>
            <div class="card-stitch px-4 py-2">
                @foreach ($location->services as $service)
                    <div class="flex items-baseline py-2 text-sm">
                        <span class="text-stone-700">{{ $service->name }}</span>
                        <span class="nota-leader" aria-hidden="true"></span>
                        <span class="mr-3 whitespace-nowrap text-xs text-stone-500">{{ $service->durationText() }}</span>
                        <span class="whitespace-nowrap font-semibold text-stone-900">{{ Format::rupiahShort($service->price_from) }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-stone-500">{{ __('Starting prices; may vary depending on fabric and design.') }}</p>
        </section>
    @endif

    {{-- Jam operasional: strip 7 hari --}}
    <section class="border-t border-stone-100 px-5 py-4">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-stone-800">
            <i class="ti ti-clock text-navy-700" aria-hidden="true"></i>{{ __('Opening hours') }}
        </h3>
        <ol class="grid grid-cols-7 gap-1 text-center">
            @foreach ($mondayFirst as $day)
                @php($hours = $week[$day])
                <li @class([
                        'rounded-lg px-0.5 py-1.5 text-[11px] leading-tight',
                        'bg-navy-700 text-white' => $day === $today,
                        'bg-red-50 text-red-700' => $day !== $today && ! $hours,
                        'bg-stone-100 text-stone-600' => $day !== $today && $hours,
                    ])
                    title="{{ $hours ? $hours['opens_at'] . '–' . $hours['closes_at'] : __('Closed') }}">
                    <span class="block font-semibold">{{ now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(($day + 6) % 7)->translatedFormat('D') }}</span>
                    <span class="block opacity-80">{{ $hours ? str_replace(':', '.', $hours['opens_at']) : __('Off') }}</span>
                </li>
            @endforeach
        </ol>
        <p class="mt-2 text-xs text-stone-500">
            @if ($week[$today])
                {{ __('Today :open–:close', ['open' => str_replace(':', '.', $week[$today]['opens_at']), 'close' => str_replace(':', '.', $week[$today]['closes_at'])]) }}
            @else
                {{ __('Closed today') }}
            @endif
        </p>
    </section>

    {{-- Ulasan --}}
    <section class="border-t border-stone-100 px-5 py-4" data-reviews>
        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-stone-800">
            <i class="ti ti-message-circle text-navy-700" aria-hidden="true"></i>{{ __('What customers say') }}
        </h3>

        @if ($reviewCount)
            <div class="mb-4 flex gap-5">
                <div class="text-center">
                    <p class="text-3xl font-bold text-stone-900">{{ number_format($average, 1) }}</p>
                    <p class="rating-star text-sm">{{ str_repeat('★', (int) round($average)) }}{{ str_repeat('☆', 5 - (int) round($average)) }}</p>
                    <p class="text-xs text-stone-500">{{ trans_choice(':count review|:count reviews', $reviewCount, ['count' => $reviewCount]) }}</p>
                </div>
                <div class="flex-1 space-y-1">
                    @foreach ($distribution as $star => $count)
                        <div class="flex items-center gap-2 text-xs text-stone-500">
                            <span class="w-3">{{ $star }}</span>
                            <span class="h-1.5 flex-1 rounded-full bg-stone-200">
                                <span class="block h-1.5 rounded-full bg-terra-500" style="width: {{ $reviewCount ? round($count / $reviewCount * 100) : 0 }}%"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($tagCounts)
                <div class="mb-4 flex flex-wrap gap-1.5">
                    @foreach ($tagCounts as $tag => $count)
                        <span class="chip cursor-default">{{ \App\Enums\ReviewTag::from($tag)->label() }} · {{ $count }}</span>
                    @endforeach
                </div>
            @endif

            <ul class="space-y-3">
                @foreach ($reviews as $review)
                    <li class="border-l-2 border-terra-400 pl-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="rating-star">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                            <span class="font-semibold text-stone-700">{{ $review->user->name ?? __('Anonymous') }}</span>
                            <span class="text-stone-400">· {{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-stone-700">{{ $review->review }}</p>

                        @if ($review->reply)
                            <div class="mt-2 rounded-lg bg-stone-50 px-3 py-2">
                                <p class="flex items-center gap-1 text-xs font-semibold text-navy-800">
                                    <i class="ti ti-corner-down-right" aria-hidden="true"></i>{{ __('Reply from the tailor') }}
                                    <span class="font-normal text-stone-400">· {{ $review->replied_at?->diffForHumans() }}</span>
                                </p>
                                <p class="mt-0.5 whitespace-pre-line text-sm text-stone-600">{{ $review->reply }}</p>
                            </div>
                        @endif

                        @auth
                            @if ($review->user_id !== auth()->id())
                                <details class="mt-1" data-report>
                                    <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-xs text-stone-400 hover:text-red-600 [&::-webkit-details-marker]:hidden">
                                        <i class="ti ti-flag" aria-hidden="true"></i>{{ __('Report') }}
                                    </summary>
                                    <form data-report-form action="{{ route('reviews.report', $review) }}" class="mt-2 space-y-2 rounded-lg border border-stone-200 bg-white p-3">
                                        <p class="text-xs font-semibold text-stone-700">{{ __('Why are you reporting this review?') }}</p>
                                        @foreach (\App\Enums\ReportReason::cases() as $reason)
                                            <label class="flex items-center gap-2 text-xs text-stone-600">
                                                <input type="radio" name="reason" value="{{ $reason->value }}" required
                                                       class="h-3.5 w-3.5 border-stone-300 text-red-600 focus:ring-red-500">
                                                {{ $reason->label() }}
                                            </label>
                                        @endforeach
                                        <textarea name="note" rows="2" maxlength="300" class="input py-1.5 text-xs" placeholder="{{ __('Additional note (optional)') }}"></textarea>
                                        <p data-report-error class="hidden text-xs text-red-600" role="alert"></p>
                                        <button type="submit" class="btn-danger px-3 py-1.5 text-xs">{{ __('Send report') }}</button>
                                    </form>
                                </details>
                            @endif
                        @endauth
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-stone-500">{{ __('No reviews for this tailor yet.') }} {{ __('Be the first to leave a review!') }}</p>
        @endif

        {{-- Form ulasan --}}
        <div class="mt-5 rounded-xl bg-stone-50 p-4">
            <h4 class="mb-2 text-sm font-semibold text-stone-800">{{ __('Write a review') }}</h4>
            @auth
                <form data-review-form action="{{ route('submit-review') }}" class="space-y-3">
                    <input type="hidden" name="location_id" value="{{ $location->id }}">

                    <fieldset>
                        <legend class="sr-only">{{ __('Rating') }}</legend>
                        <div class="flex gap-1" data-star-input>
                            @for ($star = 1; $star <= 5; $star++)
                                <label class="cursor-pointer text-2xl text-stone-300 transition" data-star="{{ $star }}">
                                    <input type="radio" name="rating" value="{{ $star }}" class="sr-only" @checked($star === 5) required>
                                    <span aria-hidden="true">★</span>
                                    <span class="sr-only">{{ trans_choice(':count star|:count stars', $star, ['count' => $star]) }}</span>
                                </label>
                            @endfor
                        </div>
                    </fieldset>

                    <fieldset class="flex flex-wrap gap-1.5">
                        <legend class="mb-1 text-xs text-stone-500">{{ __('What stood out? (optional)') }}</legend>
                        @foreach ($reviewTags as $tag)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="tags[]" value="{{ $tag->value }}" class="peer sr-only">
                                <span class="chip peer-checked:border-navy-700 peer-checked:bg-navy-50 peer-checked:text-navy-700 peer-focus-visible:ring-2 peer-focus-visible:ring-navy-600/60">
                                    {{ $tag->label() }}
                                </span>
                            </label>
                        @endforeach
                    </fieldset>

                    <textarea name="review" rows="3" maxlength="500" required class="input" placeholder="{{ __('Share your experience...') }}"></textarea>
                    <p data-review-error class="hidden text-sm text-red-600" role="alert"></p>
                    <button type="submit" class="btn-primary w-full">{{ __('Submit review') }}</button>
                </form>
            @else
                <p class="mb-3 text-sm text-stone-600">{{ __('You need to log in to write a review.') }}</p>
                <a href="{{ route('login') }}" class="btn-primary">{{ __('Log in now') }}</a>
            @endauth
        </div>
    </section>

    @if ($photos->whereNotNull('credit')->isNotEmpty())
        <p class="border-t border-stone-100 px-5 py-3 text-[11px] leading-relaxed text-stone-400">
            {{ __('Photos') }}:
            @foreach ($photos->whereNotNull('credit')->unique('credit') as $photo)
                <a href="{{ $photo->source_url }}" target="_blank" rel="noopener" class="hover:underline">{{ $photo->credit }}</a>@if (! $loop->last), @endif
            @endforeach
            · Wikimedia Commons
        </p>
    @endif
</article>
