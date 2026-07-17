<?php

namespace App\Http\Requests;

use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FormPermintaanStoreRequest extends FormRequest
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
            'user_id'                     => ['nullable', 'exists:users,id'],
            'branch_id'                   => ['nullable', 'exists:branches,id'],
            'priority'                    => ['required', 'in:low,medium,high,urgent'],
            'ticket_id'                   => ['nullable', 'exists:tickets,id'],
            'request_type'                => ['required', 'in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa'],
            'fa_number'                   => ['required_if:request_type,penggantian_produk_lama,servis,penggantian_part', 'nullable', 'string', 'max:100'],
            'reason'                      => ['required_if:request_type,pembelian_produk_baru', 'nullable', 'string'],
            'items'                       => ['required', 'array', 'min:1', 'max:20'],
            'items.*.id'                  => ['nullable', 'integer', 'exists:form_permintaan_items,id'],
            'items.*.product_description' => ['required', 'string', 'max:65000'],
            'items.*.quantity'            => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.uom'                 => ['nullable', 'string', 'max:50'],
            'items.*.notes'               => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Jika request tidak menyertakan branch_id (user biasa), wajib punya branch di akun
                if (!$this->input('branch_id') && !$this->user()?->branch_id) {
                    $validator->errors()->add('branch_id', 'Akun Anda belum terhubung ke outlet/cabang.');
                }
            },
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'branch_id'                   => 'Outlet',
            'priority'                    => 'Prioritas',
            'ticket_id'                   => 'Ticket',
            'request_type'                => 'Jenis Permintaan',
            'fa_number'                   => 'Nomor FA',
            'reason'                      => 'Alasan',
            'items'                       => 'Item',
            'items.*.product_description' => 'Deskripsi Produk',
            'items.*.quantity'            => 'Jumlah',
            'items.*.uom'                 => 'Satuan',
            'items.*.notes'               => 'Catatan',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (is_array($item) && array_key_exists('product_description', $item)) {
                    $item['product_description'] = RichTextSanitizer::clean($item['product_description']);
                }

                return $item;
            })
            ->all();

        $this->merge([
            'items' => $items,
        ]);
    }
}
