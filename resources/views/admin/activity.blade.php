@extends('layouts.admin', ['title' => __('Activity log')])

@php
    $icons = [
        'created' => ['plus', 'bg-emerald-50 text-emerald-700'],
        'updated' => ['pencil', 'bg-navy-50 text-navy-700'],
        'deleted' => ['trash', 'bg-red-50 text-red-700'],
        'force_deleted' => ['trash-x', 'bg-red-50 text-red-700'],
        'restored' => ['restore', 'bg-emerald-50 text-emerald-700'],
        'published' => ['world', 'bg-emerald-50 text-emerald-700'],
        'unpublished' => ['eye-off', 'bg-amber-50 text-amber-800'],
        'dismissed' => ['flag', 'bg-stone-100 text-stone-600'],
        'exported' => ['download', 'bg-stone-100 text-stone-600'],
        'login' => ['login', 'bg-stone-100 text-stone-600'],
        'logout' => ['logout', 'bg-stone-100 text-stone-600'],
        'password' => ['lock', 'bg-stone-100 text-stone-600'],
        'invited' => ['link', 'bg-navy-50 text-navy-700'],
        'revoked' => ['user-x', 'bg-red-50 text-red-700'],
        'joined' => ['user-check', 'bg-emerald-50 text-emerald-700'],
        'replied' => ['message-reply', 'bg-navy-50 text-navy-700'],
        'closed' => ['calendar-off', 'bg-amber-50 text-amber-800'],
        'reopened' => ['door-enter', 'bg-emerald-50 text-emerald-700'],
    ];
    $actionLabels = [
        'created' => __('Created'), 'updated' => __('Updated'), 'deleted' => __('Moved to trash'),
        'restored' => __('Restored'), 'force_deleted' => __('Permanently deleted'), 'published' => __('Published'),
        'unpublished' => __('Unpublished'), 'dismissed' => __('Reports dismissed'), 'exported' => __('Exported'),
        'login' => __('Logged in'), 'logout' => __('Logged out'), 'password' => __('Password changed'),
        'invited' => __('Partner invited'), 'revoked' => __('Partner access revoked'), 'joined' => __('Partner joined'),
        'replied' => __('Review replied'), 'closed' => __('Temporarily closed'), 'reopened' => __('Reopened'),
    ];
@endphp

@section('content')
    <form method="GET" class="mb-5 flex flex-wrap gap-2" role="search">
        <select name="admin" class="input w-auto" aria-label="{{ __('Filter by admin') }}">
            <option value="">{{ __('All admins') }}</option>
            @foreach ($admins as $option)
                <option value="{{ $option->id }}" @selected($filters['admin'] === $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
        <select name="action" class="input w-auto" aria-label="{{ __('Filter by action') }}">
            <option value="">{{ __('All actions') }}</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $actionLabels[$action] ?? $action }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline">{{ __('Filter') }}</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.activity.index') }}" class="btn-ghost" title="{{ __('Reset filters') }}" aria-label="{{ __('Reset filters') }}"><i class="ti ti-x" aria-hidden="true"></i></a>
        @endif
    </form>

    <div class="card divide-y divide-stone-100">
        @forelse ($logs as $log)
            @php([$icon, $tone] = $icons[$log->action] ?? ['point', 'bg-stone-100 text-stone-600'])
            <div class="flex items-start gap-3 px-5 py-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tone }}" title="{{ $actionLabels[$log->action] ?? $log->action }}">
                    <i class="ti ti-{{ $icon }}" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-stone-800">{{ $log->message() }}</p>
                    <p class="mt-0.5 text-xs text-stone-500">
                        @if ($log->tailor_account_id)
                            <span class="font-medium text-stone-600">{{ $log->tailorAccount->name ?? __('Deleted partner') }}</span>
                            <span class="badge bg-emerald-50 px-1.5 py-0 text-[10px] text-emerald-700">{{ __('Partner') }}{{ $log->tailorAccount?->location ? ' · ' . $log->tailorAccount->location->name : '' }}</span>
                        @else
                            <span class="font-medium text-stone-600">{{ $log->admin->name ?? __('Deleted admin') }}</span>
                        @endif
                        · <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->translatedFormat('j M Y, H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                    </p>
                </div>
            </div>
        @empty
            <p class="px-5 py-12 text-center text-stone-500">{{ __('No activity yet.') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
