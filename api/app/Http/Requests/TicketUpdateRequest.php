<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class TicketUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', TicketStatus::values())],
            'priority' => ['sometimes', 'string', 'in:' . implode(',', TicketPriority::values())],
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'assigned_staff' => ['sometimes', 'array'],
            'assigned_staff.*' => ['exists:users,id'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function attributes()
    {
        return [
            'user_id' => 'User',
            'title' => 'Judul Tiket',
            'description' => 'Deskripsi',
            'status' => 'Status',
            'priority' => 'Prioritas',
            'branch_id' => 'Cabang',
            'assigned_staff' => 'Staff yang Ditugaskan',
            'completed_at' => 'Tanggal Selesai',
        ];
    }
}
