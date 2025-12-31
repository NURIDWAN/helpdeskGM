<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\WorkOrderStatus;

class WorkOrderStoreRequest extends FormRequest
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
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'assigned_to' => ['required', 'exists:users,id'],
            'number' => ['nullable', 'string', 'max:100', 'unique:work_orders,number'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:' . implode(',', WorkOrderStatus::values())],
            'damage_unit' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
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
