<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\RecurringItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'occurrence_id' => ['required', Rule::exists(PaymentOccurrence::class, 'id')],
            'invoice_number' => ['required', 'string', 'max:255', Rule::unique(Invoice::class, 'invoice_number')],
            'invoice_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'invoice_url' => ['nullable', 'url', 'max:2048'],
            'invoice_pdf_path' => ['nullable', 'string', 'max:2048'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $occurrence = PaymentOccurrence::query()->find($this->integer('occurrence_id'));

            if (! $occurrence) {
                return;
            }

            if ($occurrence->payment_method !== RecurringItem::METHOD_BANK_TRANSFER) {
                $validator->errors()->add('occurrence_id', 'Счёт создаётся только для безналичных начислений.');
            }

            if (Invoice::query()->where('occurrence_id', $occurrence->id)->exists()) {
                $validator->errors()->add('occurrence_id', 'Для этого начисления уже есть счёт.');
            }
        });
    }
}
