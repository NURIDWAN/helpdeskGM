<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElectricityMeterUpdateRequest extends FormRequest
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
            'branch_id' => 'sometimes|required|integer|exists:branches,id',
            'meter_name' => 'sometimes|required|string|max:255',
            'meter_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'power_capacity' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
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
            'branch_id.required' => 'Cabang wajib dipilih',
            'branch_id.exists' => 'Cabang tidak ditemukan',
            'meter_name.required' => 'Nama meter wajib diisi',
            'meter_name.max' => 'Nama meter maksimal 255 karakter',
            'power_capacity.numeric' => 'Daya harus berupa angka',
            'power_capacity.min' => 'Daya tidak boleh negatif',
        ];
    }
}
