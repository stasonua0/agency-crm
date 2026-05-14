<?php

namespace App\Http\Requests;

use App\Models\Payee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayeeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Payee::TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'requisites' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(Payee::STATUSES)],
        ];
    }
}
