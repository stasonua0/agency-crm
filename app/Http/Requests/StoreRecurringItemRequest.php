<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', Rule::exists(Client::class, 'id')],
            'project_id' => ['nullable', Rule::exists(Project::class, 'id')],
            'service_id' => ['required', Rule::exists(Service::class, 'id')],
            'operation_type' => ['required', Rule::in(RecurringItem::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'periodicity' => ['required', Rule::in(RecurringItem::PERIODICITIES)],
            'start_date' => ['required', 'date'],
            'next_payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(RecurringItem::PAYMENT_METHODS)],
            'contractor_name' => ['nullable', 'string', 'max:255'],
            'contractor_amount' => ['nullable', 'required_with:contractor_name', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'status' => ['required', Rule::in(RecurringItem::STATUSES)],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('project_id')) {
                $project = Project::query()->find($this->integer('project_id'));

                if ($project && $project->client_id !== $this->integer('client_id')) {
                    $validator->errors()->add('project_id', 'Проект должен принадлежать выбранному клиенту.');
                }
            }
        });
    }
}
