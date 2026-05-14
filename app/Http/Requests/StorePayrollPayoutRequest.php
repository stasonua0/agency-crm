<?php

namespace App\Http\Requests;

use App\Models\Payee;
use App\Models\PayrollPayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollPayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', Rule::exists(Payee::class, 'id')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payout_date' => ['required', 'date'],
            'type' => ['required', Rule::in(PayrollPayout::TYPES)],
            'status' => ['required', Rule::in(PayrollPayout::STATUSES)],
            'comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $employee = Payee::query()->find($this->integer('employee_id'));

            if (! $employee) {
                return;
            }

            if ($employee->type !== Payee::TYPE_EMPLOYEE) {
                $validator->errors()->add('employee_id', 'Выберите получателя с типом “Сотрудник”.');
            }

            if ($employee->status !== Payee::STATUS_ACTIVE) {
                $validator->errors()->add('employee_id', 'Сотрудник должен быть активным.');
            }
        });
    }
}
