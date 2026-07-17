<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DailyRecord;
use Carbon\Carbon;

class DailyRecordUpdateRequest extends FormRequest
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
        // Laravel apiResource uses snake_case singular: 'daily_record'
        $dailyRecordId = $this->route('daily_record');
        $dailyRecord = $dailyRecordId ? DailyRecord::find($dailyRecordId) : null;

        return [
            'branch_id' => [
                'sometimes',
                'exists:branches,id',
                function ($attribute, $value, $fail) use ($dailyRecordId, $dailyRecord) {
                    if ($value && $dailyRecord) {
                        // Gunakan field 'date' untuk cek uniqueness per cabang per hari
                        $recordDate = $dailyRecord->date;

                        $exists = DailyRecord::where('branch_id', $value)
                            ->whereDate('date', $recordDate)
                            ->where('id', '!=', $dailyRecordId)
                            ->exists();

                        if ($exists) {
                            $fail('Cabang ini sudah memiliki catatan harian untuk tanggal tersebut. Setiap cabang hanya dapat membuat 1 catatan harian per hari.');
                        }
                    }
                },
            ],
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'], // Untuk admin menentukan PIC
            'total_customers' => ['sometimes', 'nullable', 'integer', 'min:0'],
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

    protected function prepareForValidation()
    {
        // Hanya override branch_id jika user TIDAK punya akses view-all (bukan admin)
        // Admin bisa pilih branch apapun dari form
        if (!$this->user()->can('daily-record-view-all') && $this->user()->branch) {
            $this->merge([
                'branch_id' => $this->user()->branch->id
            ]);
        }
    }
}
