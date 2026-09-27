<?php

namespace Modules\User\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tətbiq ünvanı redaktə edəndə şəhərin adını göndərir (siyahıdan
            // yenidən seçilmədikdə açar əlində olmur), yeni ünvanda isə açarı.
            // Hər ikisi qəbul edilir — servis `City::findMatching()` ilə açara
            // çevirir və tanımadığı şəhəri onsuz da rədd edir.
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'town_village_district' => ['sometimes', 'required', 'string', 'max:255'],
            'street_building_number' => ['sometimes', 'required', 'string', 'max:255'],
            'unit_floor_apartment' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            // Könüllü: müştəri istəsə xəritədən yer seçir.
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
