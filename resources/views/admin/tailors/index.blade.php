@extends('layouts.admin', ['title' => __('Tailors')])

@section('actions')
    <a href="{{ route('admin.tailors.create') }}" class="btn-primary py-1.5">
        <i class="ti ti-plus" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('Add tailor') }}</span>
    </a>
@endsection

@section('content')
    <form method="GET" class="mb-5 flex max-w-md gap-2" role="search">
        <div class="relative flex-1">
            <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $search }}" class="input pl-9" placeholder="{{ __('Search name or address...') }}" aria-label="{{ __('Search tailors') }}">
        </div>
        <button type="submit" class="btn-outline">{{ __('Search') }}</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold">{{ __('Tailor') }}</th>
                        <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                        <th scope="col" class="px-5 py-3 font-semibold">{{ __('Rating') }}</th>
                        <th scope="col" class="hidden px-5 py-3 font-semibold md:table-cell">{{ __('Services') }}</th>
                        <th scope="col" class="hidden px-5 py-3 font-semibold md:table-cell">{{ __('Photos') }}</th>
                        <th scope="col" class="px-5 py-3 text-right font-semibold"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($tailors as $tailor)
                        <tr class="hover:bg-stone-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $tailor->cover_url }}" alt="" class="h-11 w-11 shrink-0 rounded-lg bg-navy-50 object-cover">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.tailors.edit', $tailor) }}" class="font-semibold text-stone-900 hover:text-navy-700">{{ $tailor->name }}</a>
                                        <p class="max-w-xs truncate text-xs text-stone-500">{{ $tailor->address }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="{{ $tailor->isOpenNow() ? 'badge-open' : 'badge-closed' }}">{{ $tailor->isOpenNow() ? __('Open') : __('Closed') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @if ($tailor->rating !== null)
                                    <span class="rating-star">★</span> {{ number_format($tailor->rating, 1) }}
                                    <span class="text-xs text-stone-400">({{ $tailor->review_count }})</span>
                                @else
                                    <span class="text-stone-400">–</span>
                                @endif
                            </td>
                            <td class="hidden px-5 py-3 text-stone-600 md:table-cell">{{ $tailor->services_count }}</td>
                            <td class="hidden px-5 py-3 text-stone-600 md:table-cell">{{ $tailor->photos_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('penjahit.show', $tailor->slug) }}" target="_blank" rel="noopener" class="btn-ghost px-2 py-1.5" title="{{ __('View on site') }}" aria-label="{{ __('View on site') }}">
                                        <i class="ti ti-external-link" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('admin.tailors.edit', $tailor) }}" class="btn-ghost px-2 py-1.5" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                        <i class="ti ti-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('admin.tailors.destroy', $tailor) }}" method="POST"
                                          data-confirm="{{ __('Delete :name along with all its services, photos, and reviews?', ['name' => $tailor->name]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                            <i class="ti ti-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-stone-500">
                                {{ $search ? __('No tailors match your search') : __('No tailors yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $tailors->links() }}</div>
@endsection
