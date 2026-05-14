<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Client::TYPES)],
            'legal_name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:20'],
            'kpp' => ['nullable', 'string', 'max:20'],
            'ogrn' => ['nullable', 'string', 'max:30'],
            'legal_address' => ['nullable', 'string', 'max:2000'],
            'invoice_email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(Client::STATUSES)],
        ];
    }
}
