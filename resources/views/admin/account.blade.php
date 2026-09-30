@extends('layouts.admin', ['title' => __('My account')])

@section('content')
    @include('shared.account-forms', [
        'user' => $admin,
        'routes' => ['update' => 'admin.account.update', 'password' => 'admin.account.password'],
        'profileHint' => __('The name and email you use to sign in to the admin panel.'),
    ])
@endsection
