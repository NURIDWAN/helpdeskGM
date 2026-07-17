<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DailyRecord;

class DailyRecordStoreRequest extends FormRequest
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
            'branch_id' => [
                'required',
                'exists:branches,id',
                function ($attribute, $value, $fail) {
                    $date = $this->input('date');
                    $branchId = $value;

                    $existingRecord = DailyRecord::where('branch_id', $branchId)
                        ->where('date', $date)
                        ->first();

                    if ($existingRecord) {
                        $branchName = $existingRecord->branch->name ?? 'cabang ini';
                        $fail("Cabang {$branchName} sudah memiliki catatan harian untuk tanggal {$date}.");
                    }
                },
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'user_id' => ['nullable', 'exists:users,id'], // Untuk admin menentukan PIC
            'total_customers' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes()
    {
        return [
            'branch_id' => 'Cabang',
            'date' => 'Tanggal',
            'user_id' => 'User (PIC)',
            'total_customers' => 'Total Pelanggan',
        ];
    }

    public function messages()
    {
        return [
            'date.required' => 'The date field is required.',
            'date.date_format' => 'The date field must match the format Y-m-d.',
        ];
    }

    protected function prepareForValidation()
    {
        // Ambil branch_id dari user jika user punya branch (seperti di WorkReportStoreRequest)
        if ($this->user()->branch) {
            $this->merge([
                'branch_id' => $this->user()->branch->id
            ]);
        }
        
        // Set user_id default jika belum ada atau null (untuk user biasa, default ke user yang login)
        // Admin bisa override dengan mengirim user_id yang valid
        // Gunakan filled() karena has() mengembalikan true meskipun nilai null
        if (!$this->filled('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id
            ]);
        }
    }
}

