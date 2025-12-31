<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\WorkOrderStatus;

class WorkOrderUpdateRequest extends FormRequest
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
        $workOrderId = $this->route('work_order');

        return [
            'ticket_id' => 'sometimes|nullable|exists:tickets,id',
            'assigned_to' => 'sometimes|exists:users,id',
            'number' => 'sometimes|string|max:100|unique:work_orders,number,' . $workOrderId,
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', WorkOrderStatus::values()),
            'damage_unit' => 'sometimes|nullable|string|max:255',
            'contact_person' => 'sometimes|nullable|string|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:20',
            'product_type' => 'sometimes|nullable|string|max:255',
            'brand' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'serial_number' => 'sometimes|nullable|string|max:255',
            'purchase_date' => 'sometimes|nullable|date',
        ];
    }

    public function attributes()
    {
        return [
            'ticket_id' => 'Ticket',
            'assigned_to' => 'Staff',
            'number' => 'Nomor Work Order',
            'description' => 'Deskripsi',
            'status' => 'Status',
            'damage_unit' => 'Unit Kerusakan',
            'contact_person' => 'Contact Person',
            'contact_phone' => 'Nomor Telepon/HP',
            'product_type' => 'Jenis Produk',
            'brand' => 'Merk',
            'model' => 'Tipe',
            'serial_number' => 'Nomor Seri',
            'purchase_date' => 'Tanggal Pembelian',
        ];
    }
}
