<?php

namespace App\Http\Requests;

use App\Enums\JobTemplateFrequency;
use Illuminate\Foundation\Http\FormRequest;

class JobTemplateStoreRequest extends FormRequest
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
            'description' => ['required', 'string'],
            'frequency' => ['required', 'string', 'in:' . implode(',', array_column(JobTemplateFrequency::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
            'schedule_details' => ['nullable', 'array'],
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
