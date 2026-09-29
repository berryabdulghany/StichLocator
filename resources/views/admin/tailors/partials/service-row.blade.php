{{-- Satu baris layanan di form penjahit. $index bisa berupa angka atau "__INDEX__" (template JS). --}}
<div class="grid gap-2 rounded-lg border border-stone-200 p-3 sm:grid-cols-[9rem_minmax(0,1fr)_8rem_5rem_5rem_auto] sm:items-end" data-service-row>
    <div>
        <label class="text-xs font-medium text-stone-500" for="services-{{ $index }}-category">{{ __('Category') }}</label>
        <select name="services[{{ $index }}][category]" id="services-{{ $index }}-category" class="input py-1.5" required>
            @foreach (\App\Enums\ServiceCategory::cases() as $category)
                <option value="{{ $category->value }}" @selected(($service['category'] ?? null) === $category->value)>{{ $category->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-medium text-stone-500" for="services-{{ $index }}-name">{{ __('Service name') }}</label>
        <input type="text" name="services[{{ $index }}][name]" id="services-{{ $index }}-name" value="{{ $service['name'] ?? '' }}"
               class="input py-1.5" placeholder="{{ __('e.g. Modern kebaya') }}" maxlength="100" required>
    </div>
    <div>
        <label class="text-xs font-medium text-stone-500" for="services-{{ $index }}-price">{{ __('Starting price (Rp)') }}</label>
        <input type="number" name="services[{{ $index }}][price_from]" id="services-{{ $index }}-price" value="{{ $service['price_from'] ?? '' }}"
               class="input py-1.5" min="0" step="1000" required>
    </div>
    <div>
        <label class="text-xs font-medium text-stone-500" for="services-{{ $index }}-min">{{ __('Min. days') }}</label>
        <input type="number" name="services[{{ $index }}][duration_min_days]" id="services-{{ $index }}-min" value="{{ $service['duration_min_days'] ?? 1 }}"
               class="input py-1.5" min="1" max="90" required>
    </div>
    <div>
        <label class="text-xs font-medium text-stone-500" for="services-{{ $index }}-max">{{ __('Max. days') }}</label>
        <input type="number" name="services[{{ $index }}][duration_max_days]" id="services-{{ $index }}-max" value="{{ $service['duration_max_days'] ?? 1 }}"
               class="input py-1.5" min="1" max="90" required>
    </div>
    <button type="button" class="btn-ghost justify-self-end px-2 py-1.5 text-red-600 hover:bg-red-50" data-remove-service aria-label="{{ __('Remove service') }}">
        <i class="ti ti-trash" aria-hidden="true"></i>
    </button>
</div>
