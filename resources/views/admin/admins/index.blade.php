@extends('layouts.admin', ['title' => __('Admins')])

@section('content')
    @php($me = auth('admin')->user())

    @error('admin')
        <div class="alert-error mb-6" role="alert">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="card divide-y divide-stone-100" aria-labelledby="admins-title">
            <h2 id="admins-title" class="px-5 py-4 font-semibold text-stone-900">{{ __('Admin accounts') }}</h2>
            @foreach ($admins as $admin)
                <div class="flex items-center gap-3 px-5 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-700 text-sm font-bold text-white">
                        {{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-stone-900">
                            {{ $admin->name }}
                            @if ($admin->is($me))
                                <span class="badge ml-1 bg-navy-50 text-navy-700">{{ __('You') }}</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-stone-500">{{ $admin->email }}</p>
                    </div>
                    @unless ($admin->is($me))
                        <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST"
                              data-confirm="{{ __('Remove admin access for :name?', ['name' => $admin->name]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                <i class="ti ti-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endunless
                </div>
            @endforeach
        </section>

        <form action="{{ route('admin.admins.store') }}" method="POST" class="card space-y-4 p-5 lg:self-start">
            @csrf
            <h2 class="font-semibold text-stone-900">{{ __('Add admin') }}</h2>

            @if ($errors->createAdmin->any())
                <div class="alert-error" role="alert">
                    @foreach ($errors->createAdmin->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label for="admin-name" class="label">{{ __('Name') }}</label>
                <input type="text" name="name" id="admin-name" value="{{ old('name') }}" class="input" required>
            </div>
            <div>
                <label for="admin-email" class="label">{{ __('Email') }}</label>
                <input type="email" name="email" id="admin-email" value="{{ old('email') }}" class="input" autocomplete="off" required>
            </div>
            <div>
                <label for="admin-password" class="label">{{ __('Password') }}</label>
                <input type="password" name="password" id="admin-password" class="input" autocomplete="new-password" minlength="8" required>
            </div>
            <div>
                <label for="admin-password-confirmation" class="label">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" id="admin-password-confirmation" class="input" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn-primary w-full">
                <i class="ti ti-user-plus" aria-hidden="true"></i>{{ __('Add admin') }}
            </button>
        </form>
    </div>
@endsection
