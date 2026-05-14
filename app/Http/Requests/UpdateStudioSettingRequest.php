<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudioSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:20'],
            'kpp' => ['nullable', 'string', 'max:20'],
            'ogrn' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:5000'],
            'bank' => ['nullable', 'string', 'max:255'],
            'checking_account' => ['nullable', 'string', 'max:50'],
            'correspondent_account' => ['nullable', 'string', 'max:50'],
            'bik' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'vat_enabled' => ['boolean'],
            'invoice_email_subject' => ['nullable', 'string', 'max:255'],
            'invoice_email_body' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
