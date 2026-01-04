<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserType;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user],
            'password' => ['sometimes', 'string', 'min:8'],
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'identity_number' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:users,identity_number,' . $this->user],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:15'],
            'type' => ['sometimes', Rule::in(array_column(UserType::cases(), 'value'))],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata Sandi',
            'branch_id' => 'Cabang',
            'position' => 'Jabatan',
            'identity_number' => 'Nomor Identitas',
            'phone_number' => 'Nomor Telepon',
            'type' => 'Tipe',
            'roles' => 'Roles',
            'roles.*' => 'Role',
        ];
    }
}
