<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DailyRecord;
use Carbon\Carbon;

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
                    // Gunakan timezone Asia/Jakarta untuk konsistensi
                    $today = Carbon::now('Asia/Jakarta')->startOfDay();
                    $tomorrow = Carbon::now('Asia/Jakarta')->endOfDay();
                    
                    $existingRecord = DailyRecord::where('branch_id', $value)
                        ->whereBetween('created_at', [$today, $tomorrow])
                        ->first();
                    
                    if ($existingRecord) {
                        $branchName = $existingRecord->branch->name ?? 'cabang ini';
                        $fail("Cabang {$branchName} sudah memiliki catatan harian untuk hari ini (tanggal: {$existingRecord->created_at->format('d/m/Y H:i')}). Setiap cabang hanya dapat membuat 1 catatan harian per hari. Silakan edit catatan yang sudah ada atau pilih cabang lain.");
                    }
                },
            ],
            'user_id' => ['nullable', 'exists:users,id'], // Untuk admin menentukan PIC
            'total_customers' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes()
    {
        return [
            'branch_id' => 'Cabang',
            'user_id' => 'User (PIC)',
            'total_customers' => 'Total Pelanggan',
        ];
    }

    public function messages()
    {
        return [
            'branch_id.unique' => 'Cabang ini sudah memiliki catatan harian untuk hari ini. Setiap cabang hanya dapat membuat 1 catatan harian per hari.',
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
        
        // Set user_id default jika belum ada (untuk user biasa, default ke user yang login)
        // Admin bisa override dengan mengirim user_id
        if (!$this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id
            ]);
        }
    }
}

