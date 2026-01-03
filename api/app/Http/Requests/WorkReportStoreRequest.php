<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\WorkReportStatus;

class WorkReportStoreRequest extends FormRequest
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
            'branch_id' => ['required', 'exists:branches,id'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'job_template_id' => ['nullable', 'exists:job_templates,id'],
            'description' => ['nullable', 'string'],
            'custom_job' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:' . implode(',', WorkReportStatus::values())],
        ];
    }

    public function attributes()
    {
        return [
            'branch_id' => 'Cabang',
            'job_template_id' => 'Template Job',
            'description' => 'Deskripsi',
            'custom_job' => 'Pekerjaan Lainnya',
            'status' => 'Status',
        ];
    }

    protected function prepareForValidation()
    {
        // First try to get branch from the selected user (user_id in request)
        if ($this->has('user_id') && $this->user_id) {
            $selectedUser = \App\Models\User::find($this->user_id);
            if ($selectedUser && $selectedUser->branch_id) {
                $this->merge([
                    'branch_id' => $selectedUser->branch_id
                ]);
                return;
            }
        }

        // Fallback: get branch from logged in user
        if ($this->user()->branch) {
            $this->merge([
                'branch_id' => $this->user()->branch->id
            ]);
        }
    }
}
