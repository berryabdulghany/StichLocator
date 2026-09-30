@extends('admin.auth.layout', ['title' => __('Link expired'), 'badge' => __('Partner')])

@section('content')
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-2xl text-amber-700">
            <i class="ti ti-link-off" aria-hidden="true"></i>
        </span>
        <h1 class="mt-4 text-2xl font-bold text-stone-900">{{ __('This link can no longer be used') }}</h1>
        <p class="mt-2 text-sm text-stone-500">
            {{ __('Invitation links are valid for :days days and can only be used once. Ask the StichLocator admin for a new link.', ['days' => \App\Models\TailorInvitation::VALID_DAYS]) }}
        </p>
        <a href="{{ route('mitra.login') }}" class="btn-primary mt-6">{{ __('Go to partner login') }}</a>
    </div>
@endsection
