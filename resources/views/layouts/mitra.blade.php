{{-- Panel mitra penjahit (kerangka tampilan ada di layouts.panel) --}}
@php
    $account = auth('tailor')->user();
    $business = $account->location;
    $unreplied = $business->reviews()->whereNull('reply')->count();

    $panel = [
        'badge' => __('Partner'),
        'context' => $business->name,
        'menu' => [
            ['route' => 'mitra.dashboard', 'active' => 'mitra.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')],
            ['route' => 'mitra.profile.edit', 'active' => 'mitra.profile.*', 'icon' => 'building-store', 'label' => __('Business profile')],
            ['route' => 'mitra.reviews.index', 'active' => 'mitra.reviews.*', 'icon' => 'message-circle', 'label' => __('Reviews'),
                'badge' => $unreplied, 'badge_title' => trans_choice(':count review awaiting reply|:count reviews awaiting reply', $unreplied)],
            ['route' => 'mitra.account.edit', 'active' => 'mitra.account.*', 'icon' => 'user-circle', 'label' => __('My account')],
        ],
        'user' => $account,
        'account_route' => 'mitra.account.edit',
        'logout_route' => 'mitra.logout',
        'external' => ['url' => route('penjahit.show', $business->slug), 'label' => __('View my page')],
    ];
@endphp

@extends('layouts.panel')
