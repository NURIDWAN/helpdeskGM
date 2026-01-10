<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkReportAttachmentStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,zip,rar',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,text/csv,application/zip,application/x-rar-compressed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File harus berupa: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, txt, csv, zip, atau rar.',
            'file.mimetypes' => 'Tipe file tidak valid. Silakan upload file dengan format yang diizinkan.',
            'file.max' => 'Ukuran file tidak boleh lebih dari 10MB.',
        ];
    }

    public function attributes()
    {
        return [
            'file' => 'File',
        ];
    }
}
