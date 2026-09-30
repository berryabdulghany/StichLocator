@extends('layouts.mitra', ['title' => __('My account')])

@section('content')
    @include('shared.account-forms', [
        'user' => $account,
        'routes' => ['update' => 'mitra.account.update', 'password' => 'mitra.account.password'],
        'profileHint' => __('The name and email you use to sign in to the partner panel.'),
    ])
@endsection
