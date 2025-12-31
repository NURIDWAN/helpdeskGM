<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UtilityCategory;
use App\Enums\UtilitySubType;

class UtilityReadingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->input('category');

        $rules = [
            'daily_record_id' => ['sometimes', 'exists:daily_records,id'],
            'category' => ['sometimes', 'in:' . implode(',', UtilityCategory::values())],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
            'photo_wbp' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
            'photo_lwbp' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
        ];

        // Sub type: hanya untuk Gas, auto general untuk Air dan Listrik
        if ($category === 'gas') {
            $rules['sub_type'] = ['sometimes', 'nullable', 'in:' . implode(',', UtilitySubType::values())];
        } else {
            $rules['sub_type'] = ['sometimes', 'nullable'];
        }

        // Meter value: required untuk Gas dan Water, tidak untuk Electricity
        if ($category === 'electricity') {
            $rules['meter_value'] = ['sometimes', 'nullable', 'numeric', 'min:0'];
        } else {
            $rules['meter_value'] = ['sometimes', 'required', 'numeric', 'min:0'];
        }

        // Fields for Gas category
        if ($category === 'gas') {
            $rules['stove_type'] = ['sometimes', 'required', 'string', 'max:255'];
            $rules['gas_type'] = ['sometimes', 'required', 'string', 'max:255'];
        } else {
            $rules['stove_type'] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules['gas_type'] = ['sometimes', 'nullable', 'string', 'max:255'];
        }

        // Fields untuk Electricity category (WBP dan LWBP) - WBP required, LWBP optional
        if ($category === 'electricity') {
            $rules['meter_value_wbp'] = ['sometimes', 'required', 'numeric', 'min:0'];
            $rules['meter_value_lwbp'] = ['sometimes', 'nullable', 'numeric', 'min:0'];
            // Photos required for electricity (when provided)
            $rules['photo_wbp'] = ['sometimes', 'required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_lwbp'] = ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo'] = ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        } else {
            $rules['meter_value_wbp'] = ['sometimes', 'nullable', 'numeric', 'min:0'];
            $rules['meter_value_lwbp'] = ['sometimes', 'nullable', 'numeric', 'min:0'];
            // Regular photo required for non-electricity (when provided)
            $rules['photo'] = ['sometimes', 'required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_wbp'] = ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_lwbp'] = ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'daily_record_id' => 'Daily Record',
            'category' => 'Kategori',
            'sub_type' => 'Sub Tipe',
            'location' => 'Lokasi',
            'meter_value' => 'Nilai Meter',
            'photo' => 'Foto',
            'photo_wbp' => 'Foto WBP',
            'photo_lwbp' => 'Foto LWBP',
            'stove_type' => 'Stove Type',
            'gas_type' => 'Gas Type',
            'meter_value_wbp' => 'Nilai Meter WBP',
            'meter_value_lwbp' => 'Nilai Meter LWBP',
        ];
    }
}
