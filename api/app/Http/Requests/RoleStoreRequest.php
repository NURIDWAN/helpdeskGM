<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:50|unique:roles,name',
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
            'name.required' => 'Nama role wajib diisi',
            'name.min' => 'Nama role minimal 3 karakter',
            'name.max' => 'Nama role maksimal 50 karakter',
            'name.unique' => 'Nama role sudah digunakan',
            'permissions.array' => 'Format permissions tidak valid',
            'permissions.*.string' => 'Setiap permission harus berupa string',
            'permissions.*.exists' => 'Permission :input tidak valid',
        ];
    }
}
