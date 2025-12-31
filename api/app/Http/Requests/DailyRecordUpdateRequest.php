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
                    if ($value) {
                        // Jika update, gunakan tanggal dari record yang sedang di-update
                        if ($dailyRecord) {
                            $recordDate = Carbon::parse($dailyRecord->created_at)->setTimezone('Asia/Jakarta');
                            $startOfDay = $recordDate->copy()->startOfDay();
                            $endOfDay = $recordDate->copy()->endOfDay();

                            $exists = DailyRecord::where('branch_id', $value)
                                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                                ->where('id', '!=', $dailyRecordId)
                                ->exists();
                        } else {
                            // Jika create baru (tidak seharusnya terjadi di update, tapi untuk safety)
                            $today = Carbon::now('Asia/Jakarta')->startOfDay();
                            $tomorrow = Carbon::now('Asia/Jakarta')->endOfDay();

                            $exists = DailyRecord::where('branch_id', $value)
                                ->whereBetween('created_at', [$today, $tomorrow])
                                ->exists();
                        }

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
        // Ambil branch_id dari user jika user punya branch (seperti di WorkReportStoreRequest)
        if ($this->user()->branch) {
            $this->merge([
                'branch_id' => $this->user()->branch->id
            ]);
        }
    }
}
