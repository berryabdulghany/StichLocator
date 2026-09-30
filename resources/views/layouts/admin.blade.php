{{-- Panel admin (kerangka tampilan ada di layouts.panel) --}}
@php
    $reportedReviews = \App\Models\ReviewReport::open()->whereHas('review')->distinct('review_id')->count('review_id');

    $panel = [
        'badge' => 'Admin',
        'menu' => [
            ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')],
            ['route' => 'admin.tailors.index', 'active' => 'admin.tailors.*', 'icon' => 'needle-thread', 'label' => __('Tailors')],
            ['route' => 'admin.reviews.index', 'active' => 'admin.reviews.*', 'icon' => 'message-circle', 'label' => __('Reviews'),
                // Jumlah laporan ulasan yang belum ditindaklanjuti
                'badge' => $reportedReviews, 'badge_title' => __(':count reported reviews', ['count' => $reportedReviews])],
            ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users', 'label' => __('Users')],
            ['route' => 'admin.trash.index', 'active' => 'admin.trash.*', 'icon' => 'trash', 'label' => __('Trash')],
            ['route' => 'admin.activity.index', 'active' => 'admin.activity.*', 'icon' => 'history', 'label' => __('Activity log')],
            ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'icon' => 'shield-lock', 'label' => __('Admins')],
        ],
        'user' => auth('admin')->user(),
        'account_route' => 'admin.account.edit',
        'logout_route' => 'admin.logout',
        'external' => ['url' => route('home'), 'label' => __('View site')],
    ];
@endphp

@extends('layouts.panel')
