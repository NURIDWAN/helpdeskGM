<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserType;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['required', 'exists:branches,id'],
            'position' => ['required', 'string', 'max:255'],
            'identity_number' => ['required', 'string', 'max:255', 'unique:users,identity_number'],
            'phone_number' => ['nullable', 'string', 'regex:/^08[0-9]{8,12}$/', 'max:15'],
            'type' => ['required', Rule::in(array_column(UserType::cases(), 'value'))],
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')],
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
