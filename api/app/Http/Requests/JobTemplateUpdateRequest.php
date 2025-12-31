<?php

namespace App\Http\Requests;

use App\Enums\JobTemplateFrequency;
use Illuminate\Foundation\Http\FormRequest;

class JobTemplateUpdateRequest extends FormRequest
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
            'description' => ['sometimes', 'string'],
            'frequency' => ['sometimes', 'string', 'in:' . implode(',', array_column(JobTemplateFrequency::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
            'branches' => ['sometimes', 'array'],
            'branches.*.branch_id' => ['required', 'exists:branches,id'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Nama Template',
            'description' => 'Deskripsi',
            'frequency' => 'Frekuensi',
            'is_active' => 'Status Aktif',
            'branches' => 'Cabang',
            'branches.*.branch_id' => 'ID Cabang',
        ];
    }
}
