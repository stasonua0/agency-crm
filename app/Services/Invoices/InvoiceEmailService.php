<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\StudioSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceEmailService
{
    public function send(Invoice $invoice, ?string $email = null): bool
    {
        $invoice->loadMissing(['client', 'occurrence.service']);

        $to = $email ?: $invoice->client?->invoice_email;

        if (! $to) {
            return false;
        }

        $settings = StudioSetting::singleton();
        $subject = $this->render(
            $settings->invoice_email_subject ?: 'Счёт {номер_счёта} на сумму {сумма}',
            $invoice,
        );
        $body = $this->render(
            $settings->invoice_email_body ?: "Здравствуйте.\n\nНаправляем счёт {номер_счёта} на сумму {сумма} по услуге {услуга}.\n\nКлиент: {клиент}",
            $invoice,
        );

        Mail::raw($body, function ($message) use ($to, $subject, $invoice) {
            $message->to($to)->subject($subject);

            if ($invoice->invoice_pdf_path && is_file(storage_path('app/'.$invoice->invoice_pdf_path))) {
                $message->attach(storage_path('app/'.$invoice->invoice_pdf_path));
            }
        });

        $invoice->update([
            'email_to' => $to,
            'email_sent_at' => now(),
            'email_raw_response' => [
                'mailer' => config('mail.default'),
                'subject' => $subject,
                'sent_at' => now()->toISOString(),
            ],
            'status' => $invoice->status === Invoice::STATUS_DRAFT ? Invoice::STATUS_SENT : $invoice->status,
        ]);

        return true;
    }

    public function sendOrFail(Invoice $invoice): void
    {
        if (! $this->send($invoice)) {
            throw ValidationException::withMessages([
                'invoice' => 'У клиента не указан email для счетов.',
            ]);
        }
    }

    private function render(string $template, Invoice $invoice): string
    {
        $serviceName = $invoice->occurrence?->service?->document_name
            ?: $invoice->occurrence?->service?->name
            ?: '';

        return strtr($template, [
            '{номер_счёта}' => $invoice->invoice_number,
            '{сумма}' => number_format((float) $invoice->amount, 2, ',', ' ').' ₽',
            '{клиент}' => $invoice->client?->short_name ?? $invoice->client?->legal_name ?? '',
            '{услуга}' => $serviceName,
        ]);
    }
}
