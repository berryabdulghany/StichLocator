@extends('layouts.mitra', ['title' => __('Business profile')])

@section('actions')
    <a href="{{ route('penjahit.show', $tailor->slug) }}" target="_blank" rel="noopener" class="btn-ghost hidden py-1.5 sm:inline-flex">
        <i class="ti ti-external-link" aria-hidden="true"></i>{{ __('View my page') }}
    </a>
@endsection

@section('content')
    <p class="mb-6 flex items-start gap-2 rounded-lg border border-navy-100 bg-navy-50 px-4 py-3 text-sm text-navy-800" role="note">
        <i class="ti ti-info-circle mt-0.5 text-lg" aria-hidden="true"></i>
        {{ __('Changes go live on the map as soon as you save. Keep prices and opening hours up to date so customers are not disappointed.') }}
    </p>

    @include('shared.tailor-form', [
        'mode' => 'mitra',
        'formAction' => route('mitra.profile.update'),
    ])
@endsection
