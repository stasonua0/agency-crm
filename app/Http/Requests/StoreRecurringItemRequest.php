<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\Payee;
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
            'contractor_id' => ['nullable', Rule::exists(Payee::class, 'id')],
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

            if ($this->filled('contractor_id')) {
                $contractor = Payee::query()->find($this->integer('contractor_id'));

                if ($contractor && $contractor->type !== Payee::TYPE_CONTRACTOR) {
                    $validator->errors()->add('contractor_id', 'Выберите получателя с типом “Подрядчик”.');
                }

                if ($contractor && $contractor->status !== Payee::STATUS_ACTIVE) {
                    $validator->errors()->add('contractor_id', 'Подрядчик должен быть активным.');
                }
            }

            if ($this->filled('contractor_id') && ! $this->filled('contractor_amount')) {
                $validator->errors()->add('contractor_amount', 'Укажите сумму подрядчику.');
            }
        });
    }
}
