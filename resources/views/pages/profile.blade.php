@extends('layouts.site', ['title' => __('My profile')])

@section('content')
<main class="mx-auto max-w-5xl px-4 py-8 lg:px-6">
    @if (session('status'))
        <div class="alert-success mb-6 flex items-center gap-2" role="status">
            <i class="ti ti-circle-check text-lg" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif

    {{-- ============================== KARTU PROFIL ============================== --}}
    <section class="card overflow-hidden">
        <div class="relative h-28 bg-navy-700">
            <div class="absolute inset-3 rounded-xl border-2 border-dashed border-white/20" aria-hidden="true"></div>
        </div>

        <div class="px-6 pb-6">
            {{-- relative: supaya avatar digambar di atas banner (yang juga relative) --}}
            <div class="relative -mt-12 flex flex-wrap items-end gap-4">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-full bg-navy-50 ring-4 ring-white">
                    @if ($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ __('Profile picture') }}" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-3xl font-bold text-navy-700">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div class="min-w-0 flex-1 pb-1">
                    <h1 class="truncate text-2xl font-bold text-stone-900">{{ $user->name }}</h1>
                    <p class="truncate text-sm text-stone-500">
                        {{ $user->email }} · {{ __('Member since :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}
                    </p>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-3 gap-3 border-t border-dashed border-stitch pt-5 text-center sm:max-w-md sm:text-left">
                <div>
                    <dt class="text-xs text-stone-500">{{ __('Reviews written') }}</dt>
                    <dd class="text-2xl font-bold text-stone-900">{{ $stats['reviews'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-stone-500">{{ __('Saved') }}</dt>
                    <dd class="text-2xl font-bold text-stone-900" data-saved-count>0</dd>
                </div>
                <div>
                    <dt class="text-xs text-stone-500">{{ __('Average given') }}</dt>
                    <dd class="text-2xl font-bold text-stone-900">
                        @if ($stats['average'])
                            <span class="rating-star">★</span> {{ number_format($stats['average'], 1) }}
                        @else
                            –
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- ============================== TAB ============================== --}}
    <div class="mt-8 flex gap-1 border-b border-stone-200" role="tablist" aria-label="{{ __('Profile sections') }}">
        @foreach (['ulasan' => ['message-circle', __('My reviews')], 'tersimpan' => ['bookmark', __('Saved')], 'pengaturan' => ['settings', __('Settings')]] as $tab => [$icon, $label])
            <button type="button" role="tab" id="tab-{{ $tab }}" aria-controls="panel-{{ $tab }}" data-tab="{{ $tab }}" class="profile-tab">
                <i class="ti ti-{{ $icon }}" aria-hidden="true"></i>{{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Ulasan saya --}}
    <section id="panel-ulasan" role="tabpanel" aria-labelledby="tab-ulasan" data-panel="ulasan" class="py-6">
        @forelse ($reviews as $review)
            <article class="card mb-3 flex gap-4 p-4">
                @if ($review->location)
                    <a href="{{ route('penjahit.show', $review->location->slug) }}" class="shrink-0">
                        <img src="{{ $review->location->cover_url }}" alt="" class="h-16 w-16 rounded-lg bg-navy-50 object-cover sm:h-20 sm:w-20">
                    </a>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            @if ($review->location)
                                <a href="{{ route('penjahit.show', $review->location->slug) }}" class="font-semibold text-stone-900 hover:text-navy-700">{{ $review->location->name }}</a>
                            @endif
                            <p class="text-xs text-stone-500">
                                <span class="rating-star">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                                · {{ $review->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <form action="{{ route('reviews.destroy-own', $review) }}" method="POST"
                              onsubmit="return confirm(@js(__('Delete this review?')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost px-2 py-1 text-xs text-red-600 hover:bg-red-50">
                                <i class="ti ti-trash" aria-hidden="true"></i>{{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                    <p class="mt-2 text-sm text-stone-700">{{ $review->review }}</p>
                    @if ($review->tags)
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($review->tags as $tag)
                                @if ($label = \App\Enums\ReviewTag::tryFrom($tag)?->label())
                                    <span class="badge-tag">{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="card-stitch flex flex-col items-center p-10 text-center">
                <i class="ti ti-message-circle text-4xl text-stone-300" aria-hidden="true"></i>
                <p class="mt-3 font-semibold text-stone-800">{{ __('You have not written any reviews yet') }}</p>
                <p class="mt-1 text-sm text-stone-500">{{ __('Share your experience to help others find a good tailor.') }}</p>
                <a href="{{ route('dashboard') }}" class="btn-primary mt-5"><i class="ti ti-map-2" aria-hidden="true"></i>{{ __('Find tailors') }}</a>
            </div>
        @endforelse
    </section>

    {{-- Penjahit tersimpan (dari localStorage browser, lihat resources/js/profile.js) --}}
    <section id="panel-tersimpan" role="tabpanel" aria-labelledby="tab-tersimpan" data-panel="tersimpan" class="py-6" hidden>
        <div class="grid gap-3 sm:grid-cols-2" data-saved-list
             data-source="{{ route('locations.get') }}" data-detail-base="{{ url('penjahit') }}"></div>
        <div class="card-stitch flex flex-col items-center p-10 text-center" data-saved-empty hidden>
            <i class="ti ti-bookmark text-4xl text-stone-300" aria-hidden="true"></i>
            <p class="mt-3 font-semibold text-stone-800">{{ __('No saved tailors yet') }}</p>
            <p class="mt-1 text-sm text-stone-500">{{ __('Tap the bookmark icon on a tailor to save it here.') }}</p>
            <a href="{{ route('dashboard') }}" class="btn-primary mt-5"><i class="ti ti-map-2" aria-hidden="true"></i>{{ __('Find tailors') }}</a>
        </div>
        <p class="mt-4 text-xs text-stone-400">{{ __('Saved tailors are stored in this browser.') }}</p>
    </section>

    {{-- Pengaturan akun --}}
    <section id="panel-pengaturan" role="tabpanel" aria-labelledby="tab-pengaturan" data-panel="pengaturan" class="grid gap-6 py-6 lg:grid-cols-2" hidden>
        {{-- Profil --}}
        <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" class="card space-y-5 p-6">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold text-stone-900">{{ __('Profile') }}</h2>

            @if ($errors->default->any())
                <div class="alert-error" role="alert">
                    @foreach ($errors->default->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full bg-navy-50">
                    <img id="avatar-preview" src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : '' }}" alt=""
                         @class(['h-full w-full object-cover', 'hidden' => ! $user->profile_picture])>
                    <span id="avatar-placeholder" @class(['flex h-full w-full items-center justify-center text-2xl font-bold text-navy-700', 'hidden' => $user->profile_picture])>
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                </div>
                <div>
                    <label for="profile_picture" class="btn-outline cursor-pointer py-1.5">
                        <i class="ti ti-camera" aria-hidden="true"></i>{{ __('Change photo') }}
                    </label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <p class="mt-1 text-xs text-stone-500">{{ __('Max 2 MB. JPG, PNG, or WebP.') }}</p>
                </div>
            </div>

            <div>
                <label for="name" class="label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input" autocomplete="name" required>
            </div>

            <div>
                <label for="email" class="label">{{ __('Email') }}</label>
                <input type="email" id="email" value="{{ $user->email }}" class="input bg-stone-50 text-stone-500" readonly>
                <p class="mt-1 text-xs text-stone-500">{{ __('Email cannot be changed.') }}</p>
            </div>

            <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
        </form>

        <div class="space-y-6">
            {{-- Password --}}
            <form action="{{ route('user.password.update') }}" method="POST" class="card space-y-5 p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold text-stone-900">{{ __('Change password') }}</h2>

                @if ($errors->updatePassword->any())
                    <div class="alert-error" role="alert">
                        @foreach ($errors->updatePassword->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="current_password" class="label">{{ __('Current password') }}</label>
                    <input type="password" name="current_password" id="current_password" class="input" autocomplete="current-password" required>
                </div>
                <div>
                    <label for="password" class="label">{{ __('New password') }}</label>
                    <input type="password" name="password" id="password" class="input" autocomplete="new-password" minlength="8" required>
                </div>
                <div>
                    <label for="password_confirmation" class="label">{{ __('Confirm new password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input" autocomplete="new-password" minlength="8" required>
                </div>

                <button type="submit" class="btn-outline">{{ __('Update password') }}</button>
            </form>

            {{-- Keluar --}}
            <form action="{{ route('logout') }}" method="POST" class="card flex items-center justify-between gap-4 p-6">
                @csrf
                <div>
                    <h2 class="font-semibold text-stone-900">{{ __('Log out') }}</h2>
                    <p class="text-sm text-stone-500">{{ __('End your session on this device.') }}</p>
                </div>
                <button type="submit" class="btn-ghost text-red-600 hover:bg-red-50">
                    <i class="ti ti-logout" aria-hidden="true"></i>{{ __('Log out') }}
                </button>
            </form>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    @vite('resources/js/profile.js')
@endpush
