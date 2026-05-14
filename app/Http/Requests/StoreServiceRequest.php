<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(Service::STATUSES)],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
