@php
    $editing = $tailor->exists;
    $title = $editing ? $tailor->name : __('Add tailor');

    // Urutan hari untuk form: Senin dulu (0 = Minggu mengikuti Carbon)
    $days = [1, 2, 3, 4, 5, 6, 0];
    $dayName = fn (int $day) => now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(($day + 6) % 7)->translatedFormat('l');

    $services = old('services', $tailor->services?->map->only(['category', 'name', 'price_from', 'duration_min_days', 'duration_max_days'])->map(
        fn ($service) => [...$service, 'category' => $service['category']->value]
    )->all() ?? []);
@endphp

@extends('layouts.admin', ['title' => $title])

@section('actions')
    <a href="{{ route('admin.tailors.index') }}" class="btn-ghost hidden py-1.5 sm:inline-flex">
        <i class="ti ti-arrow-left" aria-hidden="true"></i>{{ __('Back to list') }}
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert-error mb-6" role="alert">
            <p class="font-semibold">{{ __('Please fix the following:') }}</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="tailor-form" method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.tailors.update', $tailor) : route('admin.tailors.store') }}"
          class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="space-y-6">
            {{-- ============================== INFORMASI DASAR ============================== --}}
            <section class="card p-6">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-stone-900">
                    <i class="ti ti-info-circle text-navy-700" aria-hidden="true"></i>{{ __('Basic information') }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="label">{{ __('Tailor name') }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $tailor->name) }}" class="input" required maxlength="255">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="label">{{ __('Address') }} *</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $tailor->address) }}" class="input" required maxlength="255"
                               placeholder="Jl. Braga No. 12, Sumur Bandung, Kota Bandung">
                        <p class="mt-1 text-xs text-stone-500">{{ __('Format: street, district, city. The district is used for the area filter.') }}</p>
                    </div>
                    <div>
                        <label for="telepon" class="label">{{ __('WhatsApp / phone') }}</label>
                        <input type="tel" name="telepon" id="telepon" value="{{ old('telepon', $tailor->telepon) }}" class="input" placeholder="0812 3456 7890" maxlength="20">
                    </div>
                    <div class="flex items-end">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700">
                            <input type="checkbox" name="offers_home_visit" value="1" class="h-4 w-4 rounded border-stone-300 text-navy-700 focus:ring-navy-600"
                                   @checked(old('offers_home_visit', $tailor->offers_home_visit))>
                            <i class="ti ti-home-move text-navy-700" aria-hidden="true"></i>{{ __('Offers home visits for measuring') }}
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="label">{{ __('Description') }}</label>
                        <textarea name="description" id="description" rows="3" class="input" maxlength="1000">{{ old('description', $tailor->description) }}</textarea>
                    </div>
                </div>
            </section>

            {{-- ============================== LOKASI ============================== --}}
            <section class="card p-6">
                <h2 class="mb-1 flex items-center gap-2 font-semibold text-stone-900">
                    <i class="ti ti-map-pin text-navy-700" aria-hidden="true"></i>{{ __('Location on the map') }}
                </h2>
                <p class="mb-4 text-sm text-stone-500">{{ __('Click the map or drag the pin to the tailor\'s location.') }}</p>
                <div id="tailor-map" class="h-72 overflow-hidden rounded-xl border border-stone-200" aria-label="{{ __('Location picker map') }}"></div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="lat" class="label">{{ __('Latitude') }} *</label>
                        <input type="number" step="any" name="lat" id="lat" value="{{ old('lat', $tailor->lat) }}" class="input" required>
                    </div>
                    <div>
                        <label for="lng" class="label">{{ __('Longitude') }} *</label>
                        <input type="number" step="any" name="lng" id="lng" value="{{ old('lng', $tailor->lng) }}" class="input" required>
                    </div>
                </div>
            </section>

            {{-- ============================== JAM BUKA ============================== --}}
            <section class="card p-6">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-stone-900">
                    <i class="ti ti-clock text-navy-700" aria-hidden="true"></i>{{ __('Opening hours') }}
                </h2>
                <div class="divide-y divide-stone-100">
                    @foreach ($days as $day)
                        @php
                            $closed = (bool) old("hours.$day.closed", $week[$day] === null);
                            $opens = old("hours.$day.opens_at", $week[$day]['opens_at'] ?? '08:00');
                            $closes = old("hours.$day.closes_at", $week[$day]['closes_at'] ?? '17:00');
                        @endphp
                        <div class="flex flex-wrap items-center gap-3 py-2.5" data-hours-row>
                            <span class="w-24 font-medium text-stone-700">{{ $dayName($day) }}</span>
                            <input type="hidden" name="hours[{{ $day }}][closed]" value="0">
                            <label class="flex items-center gap-2 text-sm text-stone-600">
                                <input type="checkbox" name="hours[{{ $day }}][closed]" value="1" data-closed-toggle
                                       class="h-4 w-4 rounded border-stone-300 text-red-600 focus:ring-red-500" @checked($closed)>
                                {{ __('Closed') }}
                            </label>
                            <div class="flex items-center gap-2" data-hours-inputs>
                                <input type="time" name="hours[{{ $day }}][opens_at]" value="{{ $opens }}" class="input w-auto py-1.5" aria-label="{{ __('Opens at') }} {{ $dayName($day) }}">
                                <span class="text-stone-400">–</span>
                                <input type="time" name="hours[{{ $day }}][closes_at]" value="{{ $closes }}" class="input w-auto py-1.5" aria-label="{{ __('Closes at') }} {{ $dayName($day) }}">
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-stone-500">{{ __('Closing time earlier than opening time means the tailor is open past midnight.') }}</p>
            </section>

            {{-- ============================== LAYANAN & HARGA ============================== --}}
            <section class="card p-6">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 font-semibold text-stone-900">
                        <i class="ti ti-receipt text-navy-700" aria-hidden="true"></i>{{ __('Services and prices') }}
                    </h2>
                    <button type="button" class="btn-outline py-1.5" data-add-service>
                        <i class="ti ti-plus" aria-hidden="true"></i>{{ __('Add service') }}
                    </button>
                </div>

                <div class="space-y-3" data-services>
                    @foreach ($services as $i => $service)
                        @include('admin.tailors.partials.service-row', ['index' => $i, 'service' => $service])
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-stone-500 {{ $services ? 'hidden' : '' }}" data-services-empty>{{ __('No services yet. Add at least one so the tailor appears in category filters.') }}</p>

                <template data-service-template>
                    @include('admin.tailors.partials.service-row', ['index' => '__INDEX__', 'service' => []])
                </template>
            </section>

            {{-- ============================== GALERI ============================== --}}
            <section class="card p-6">
                <h2 class="mb-4 flex items-center gap-2 font-semibold text-stone-900">
                    <i class="ti ti-photo text-navy-700" aria-hidden="true"></i>{{ __('Photo gallery') }}
                </h2>

                @if ($editing && $tailor->photos->isNotEmpty())
                    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($tailor->photos as $photo)
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg border border-stone-200" data-photo>
                                <img src="{{ $photo->url }}" alt="" class="aspect-[4/3] w-full object-cover">
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-1.5 bg-white/90 px-2 py-1 text-xs text-stone-700">
                                    <input type="checkbox" name="photos_delete[]" value="{{ $photo->id }}" class="h-3.5 w-3.5 rounded border-stone-300 text-red-600 focus:ring-red-500">
                                    {{ __('Delete') }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <label for="photos" class="label">{{ __('Add photos') }}</label>
                <input type="file" name="photos[]" id="photos" multiple accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-stone-500 file:mr-3 file:rounded-full file:border-0 file:bg-navy-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-navy-700 hover:file:bg-navy-100">
                <p class="mt-1 text-xs text-stone-500">{{ __('Up to 10 photos at once, max 3 MB each (JPG, PNG, WebP).') }}</p>
            </section>
        </div>

        {{-- ============================== KOLOM SAMPING ============================== --}}
        <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
            <section class="card p-5">
                <h2 class="mb-3 font-semibold text-stone-900">{{ __('Cover photo') }} @unless ($editing) * @endunless</h2>
                <div class="aspect-[4/3] overflow-hidden rounded-lg bg-navy-50">
                    <img id="cover-preview" src="{{ $tailor->cover_url }}" alt="" @class(['h-full w-full object-cover', 'hidden' => ! $tailor->cover_url])>
                    <div id="cover-placeholder" @class(['flex h-full items-center justify-center text-4xl text-navy-300', 'hidden' => $tailor->cover_url])>
                        <i class="ti ti-photo" aria-hidden="true"></i>
                    </div>
                </div>
                <label for="cover" class="btn-outline mt-3 w-full cursor-pointer">
                    <i class="ti ti-upload" aria-hidden="true"></i>{{ $editing ? __('Replace cover') : __('Choose cover') }}
                </label>
                <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" @required(! $editing)>
            </section>

            <section class="card space-y-3 p-5">
                <button type="submit" class="btn-primary w-full py-2.5">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>{{ $editing ? __('Save changes') : __('Add tailor') }}
                </button>
                @if ($editing)
                    <a href="{{ route('penjahit.show', $tailor->slug) }}" target="_blank" rel="noopener" class="btn-outline w-full">
                        <i class="ti ti-external-link" aria-hidden="true"></i>{{ __('View on site') }}
                    </a>
                    <dl class="space-y-1 border-t border-dashed border-stitch pt-3 text-xs text-stone-500">
                        <div class="flex justify-between"><dt>{{ __('Link') }}</dt><dd class="truncate pl-2 font-mono">/penjahit/{{ $tailor->slug }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('Rating') }}</dt><dd>{{ $tailor->rating !== null ? number_format($tailor->rating, 1) . ' (' . $tailor->review_count . ')' : '–' }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('Last updated') }}</dt><dd>{{ $tailor->updated_at?->diffForHumans() }}</dd></div>
                    </dl>
                    <button type="submit" form="delete-tailor" class="btn-ghost w-full text-red-600 hover:bg-red-50">
                        <i class="ti ti-trash" aria-hidden="true"></i>{{ __('Delete tailor') }}
                    </button>
                @endif
            </section>
        </aside>
    </form>

    @if ($editing)
        {{-- Form hapus di luar form utama (form tidak boleh bersarang) --}}
        <form id="delete-tailor" action="{{ route('admin.tailors.destroy', $tailor) }}" method="POST"
              data-confirm="{{ __('Delete :name along with all its services, photos, and reviews?', ['name' => $tailor->name]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
