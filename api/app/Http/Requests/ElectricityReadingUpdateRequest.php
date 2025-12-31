<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectricityReadingUpdateRequest extends FormRequest
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
        return [
            'daily_record_id' => 'sometimes|required|integer|exists:daily_records,id',
            'electricity_meter_id' => 'sometimes|required|integer|exists:electricity_meters,id',
            'meter_value_wbp' => 'required|numeric|min:0',
            'meter_value_lwbp' => 'required|numeric|min:0',
            'photo_wbp' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'photo_lwbp' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_record_id.required' => 'Daily record wajib dipilih',
            'daily_record_id.exists' => 'Daily record tidak ditemukan',
            'electricity_meter_id.required' => 'Meter listrik wajib dipilih',
            'electricity_meter_id.exists' => 'Meter listrik tidak ditemukan',
            'meter_value_wbp.numeric' => 'Nilai WBP harus berupa angka',
            'meter_value_wbp.min' => 'Nilai WBP tidak boleh negatif',
            'meter_value_lwbp.numeric' => 'Nilai LWBP harus berupa angka',
            'meter_value_lwbp.min' => 'Nilai LWBP tidak boleh negatif',
            'photo_wbp.image' => 'Foto WBP harus berupa gambar',
            'photo_wbp.max' => 'Foto WBP maksimal 5MB',
            'photo_lwbp.image' => 'Foto LWBP harus berupa gambar',
            'photo_lwbp.max' => 'Foto LWBP maksimal 5MB',
        ];
    }
}
