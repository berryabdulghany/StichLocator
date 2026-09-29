<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah/edit penjahit di panel admin.
 */
class TailorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        // Checkbox yang tidak dicentang tidak terkirim; jadikan boolean eksplisit
        $hours = collect($this->input('hours', []))
            ->map(fn ($day) => [...$day, 'closed' => filter_var($day['closed'] ?? false, FILTER_VALIDATE_BOOLEAN)])
            ->all();

        $this->merge([
            'offers_home_visit' => $this->boolean('offers_home_visit'),
            'is_published' => $this->boolean('is_published'),
            'hours' => $hours,
            'services' => array_values($this->input('services', [])),
        ]);
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'offers_home_visit' => ['boolean'],
            'is_published' => ['boolean'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],

            // Foto dikompres di server (ImageOptimizer), jadi foto langsung dari HP boleh diunggah.
            // Batas total per kiriman mengikuti post_max_size PHP (40 MB): sampul + 5 foto x 6 MB.
            'cover' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:6144'],

            // Jam buka per hari (0 = Minggu ... 6 = Sabtu)
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.closed' => ['boolean'],
            'hours.*.opens_at' => ['nullable', 'required_if:hours.*.closed,false', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'required_if:hours.*.closed,false', 'date_format:H:i'],

            // Layanan & harga ("nota jahit")
            'services' => ['nullable', 'array', 'max:20'],
            'services.*.category' => ['required', Rule::enum(ServiceCategory::class)],
            'services.*.name' => ['required', 'string', 'max:100'],
            'services.*.price_from' => ['required', 'integer', 'min:0', 'max:100000000'],
            'services.*.duration_min_days' => ['required', 'integer', 'min:1', 'max:90'],
            'services.*.duration_max_days' => ['required', 'integer', 'max:90', 'gte:services.*.duration_min_days'],

            // Galeri foto
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:6144'],
            'photos_delete' => ['nullable', 'array'],
            'photos_delete.*' => ['integer'],
            'photo_order' => ['nullable', 'array'],
            'photo_order.*' => ['integer'],
            'photo_credit' => ['nullable', 'array'],
            'photo_credit.*' => ['nullable', 'string', 'max:255'],
            'cover_photo_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('tailor name'),
            'address' => __('address'),
            'telepon' => __('phone number'),
            'cover' => __('cover photo'),
            'hours.*.opens_at' => __('opening time'),
            'hours.*.closes_at' => __('closing time'),
            'services.*.category' => __('service category'),
            'services.*.name' => __('service name'),
            'services.*.price_from' => __('starting price'),
            'services.*.duration_min_days' => __('minimum days'),
            'services.*.duration_max_days' => __('maximum days'),
            'photos.*' => __('photo'),
            'photo_credit.*' => __('photo credit'),
        ];
    }
}
