@extends('layouts.admin', ['title' => __('Users')])

@section('content')
    <form method="GET" class="mb-5 flex max-w-md gap-2" role="search">
        <div class="relative flex-1">
            <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $search }}" class="input pl-9" placeholder="{{ __('Search name or email...') }}" aria-label="{{ __('Search users') }}">
        </div>
        <button type="submit" class="btn-outline">{{ __('Search') }}</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold">{{ __('User') }}</th>
                        <th scope="col" class="px-5 py-3 font-semibold">{{ __('Reviews') }}</th>
                        <th scope="col" class="hidden px-5 py-3 font-semibold sm:table-cell">{{ __('Joined') }}</th>
                        <th scope="col" class="px-5 py-3 text-right font-semibold"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-stone-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($user->profile_picture)
                                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    @else
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-50 text-sm font-bold text-navy-700">
                                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-stone-900">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-stone-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($user->reviews_count)
                                    <a href="{{ route('admin.reviews.index', ['q' => $user->name]) }}" class="text-navy-700 hover:underline">{{ $user->reviews_count }}</a>
                                @else
                                    <span class="text-stone-400">0</span>
                                @endif
                            </td>
                            <td class="hidden whitespace-nowrap px-5 py-3 text-stone-600 sm:table-cell">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline"
                                      data-confirm="{{ __('Delete :name and all their reviews?', ['name' => $user->name]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-stone-500">{{ __('No users found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
