@php
    $editing = $tailor->exists;
    $title = $editing ? $tailor->name : __('Add tailor');
@endphp

@extends('layouts.admin', ['title' => $title])

@section('actions')
    <a href="{{ route('admin.tailors.index') }}" class="btn-ghost hidden py-1.5 sm:inline-flex">
        <i class="ti ti-arrow-left" aria-hidden="true"></i>{{ __('Back to list') }}
    </a>
@endsection

@section('content')
    @if ($editing)
        @include('admin.tailors.partials.partner-account')
    @endif

    @include('shared.tailor-form', [
        'mode' => 'admin',
        'formAction' => $editing ? route('admin.tailors.update', $tailor) : route('admin.tailors.store'),
    ])

    @if ($editing)
        {{-- Form hapus di luar form utama (form tidak boleh bersarang) --}}
        <form id="delete-tailor" action="{{ route('admin.tailors.destroy', $tailor) }}" method="POST"
              data-confirm="{{ __('Move :name to trash? It can be restored within :days days.', ['name' => $tailor->name, 'days' => \App\Models\Location::TRASH_DAYS]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
