<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'min:3',
                'max:50',
                Rule::unique('roles', 'name')->ignore($this->route('role')),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.min' => 'Nama role minimal 3 karakter',
            'name.max' => 'Nama role maksimal 50 karakter',
            'name.unique' => 'Nama role sudah digunakan',
            'permissions.array' => 'Format permissions tidak valid',
            'permissions.*.string' => 'Setiap permission harus berupa string',
            'permissions.*.exists' => 'Permission :input tidak valid',
        ];
    }
}
