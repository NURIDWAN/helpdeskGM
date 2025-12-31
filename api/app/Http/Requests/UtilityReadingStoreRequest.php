<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UtilityCategory;
use App\Enums\UtilitySubType;

class UtilityReadingStoreRequest extends FormRequest
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
            'daily_record_id' => ['required', 'exists:daily_records,id'],
            'category' => ['required', 'in:' . implode(',', UtilityCategory::values())],
            'location' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
            'photo_wbp' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
            'photo_lwbp' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB max
        ];
        
        // Sub type: hanya untuk Gas, auto general untuk Air dan Listrik
        if ($category === 'gas') {
            $rules['sub_type'] = ['nullable', 'in:' . implode(',', UtilitySubType::values())];
        } else {
            // Air dan Listrik: sub_type auto general (nullable)
            $rules['sub_type'] = ['nullable'];
        }
        
        // Meter value: required untuk Gas dan Water, tidak untuk Electricity
        if ($category === 'electricity') {
            $rules['meter_value'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $rules['meter_value'] = ['required', 'numeric', 'min:0'];
        }
        
        // Fields for Gas category
        if ($category === 'gas') {
            $rules['stove_type'] = ['required', 'string', 'max:255'];
            $rules['gas_type'] = ['required', 'string', 'max:255'];
        } else {
            $rules['stove_type'] = ['nullable', 'string', 'max:255'];
            $rules['gas_type'] = ['nullable', 'string', 'max:255'];
        }
        
        // Fields untuk Electricity category (WBP dan LWBP) - WBP required, LWBP optional
        if ($category === 'electricity') {
            $rules['meter_value_wbp'] = ['required', 'numeric', 'min:0'];
            $rules['meter_value_lwbp'] = ['nullable', 'numeric', 'min:0'];
            // Photos required for electricity
            $rules['photo_wbp'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_lwbp'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        } else {
            $rules['meter_value_wbp'] = ['nullable', 'numeric', 'min:0'];
            $rules['meter_value_lwbp'] = ['nullable', 'numeric', 'min:0'];
            // Regular photo required for non-electricity
            $rules['photo'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_wbp'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
            $rules['photo_lwbp'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
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

