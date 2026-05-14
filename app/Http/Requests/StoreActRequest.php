<?php

namespace App\Http\Requests;

use App\Models\Act;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'occurrence_id' => ['required', Rule::exists(PaymentOccurrence::class, 'id')],
            'invoice_id' => ['nullable', Rule::exists(Invoice::class, 'id')],
            'act_number' => ['required', 'string', 'max:255', Rule::unique(Act::class, 'act_number')],
            'act_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Act::STATUSES)],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('invoice_id')) {
                return;
            }

            $invoice = Invoice::query()->find($this->integer('invoice_id'));

            if ($invoice && $invoice->occurrence_id !== $this->integer('occurrence_id')) {
                $validator->errors()->add('invoice_id', 'Счёт должен относиться к выбранному начислению.');
            }
        });
    }
}
