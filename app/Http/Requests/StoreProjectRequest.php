<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', Rule::exists(Client::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'budget' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
