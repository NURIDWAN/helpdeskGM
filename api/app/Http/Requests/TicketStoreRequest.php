<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class TicketStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'description' => ['required', 'string'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', TicketStatus::values())],
            'priority' => ['sometimes', 'string', 'in:' . implode(',', TicketPriority::values())],
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
            'assigned_staff' => ['sometimes', 'array'],
            'assigned_staff.*' => ['exists:users,id'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function attributes()
    {
        return [
            'user_id' => 'User',
            'description' => 'Deskripsi',
            'status' => 'Status',
            'priority' => 'Prioritas',
            'branch_id' => 'Cabang',
            'category_id' => 'Kategori',
            'assigned_staff' => 'Staff yang Ditugaskan',
            'completed_at' => 'Tanggal Selesai',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);

        // Only auto-assign branch_id for non-admin users when not provided
        // Admins can select any branch from the form
        $user = $this->user();
        if (!$this->has('branch_id') || !$this->branch_id) {
            // Use branch_id directly instead of branch->id to avoid null error
            if ($user->branch_id) {
                $this->merge([
                    'branch_id' => $user->branch_id,
                ]);
            }
        }
    }
}
