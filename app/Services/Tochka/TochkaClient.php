<?php

namespace App\Services\Tochka;

use App\Models\Act;
use App\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TochkaClient
{
    public function __construct(
        private readonly array $config = [],
    ) {
    }

    public function createInvoice(Invoice $invoice): array
    {
        if ($this->usesSandboxStub()) {
            return $this->sandboxResponse('invoice', $invoice->invoice_number, [
                'amount' => (string) $invoice->amount,
                'external_id' => 'sandbox-invoice-'.$invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_url' => "https://sandbox.tochka.example/invoices/{$invoice->id}",
            ]);
        }

        return $this->request()
            ->post('/invoices', $this->invoicePayload($invoice))
            ->throw()
            ->json();
    }

    public function getInvoiceFile(string $externalId): array
    {
        if ($this->usesSandboxStub()) {
            return [
                'external_id' => $externalId,
                'url' => "https://sandbox.tochka.example/invoices/{$externalId}.pdf",
                'path' => null,
            ];
        }

        return $this->request()
            ->get("/invoices/{$externalId}/file")
            ->throw()
            ->json();
    }

    public function sendInvoiceEmail(string $externalId, string $email): array
    {
        if ($this->usesSandboxStub()) {
            return [
                'external_id' => $externalId,
                'email' => $email,
                'sent' => true,
            ];
        }

        return $this->request()
            ->post("/invoices/{$externalId}/email", ['email' => $email])
            ->throw()
            ->json();
    }

    public function getInvoiceStatus(string $externalId): array
    {
        if ($this->usesSandboxStub()) {
            return [
                'external_id' => $externalId,
                'status' => 'sent',
            ];
        }

        return $this->request()
            ->get("/invoices/{$externalId}/status")
            ->throw()
            ->json();
    }

    public function createAct(Act $act): array
    {
        if ($this->usesSandboxStub()) {
            return $this->sandboxResponse('act', $act->act_number, [
                'amount' => (string) $act->amount,
                'external_id' => 'sandbox-act-'.$act->id,
                'act_number' => $act->act_number,
            ]);
        }

        return $this->request()
            ->post('/acts', $this->actPayload($act))
            ->throw()
            ->json();
    }

    public function getActFile(string $externalId): array
    {
        if ($this->usesSandboxStub()) {
            return [
                'external_id' => $externalId,
                'url' => "https://sandbox.tochka.example/acts/{$externalId}.pdf",
                'path' => null,
            ];
        }

        return $this->request()
            ->get("/acts/{$externalId}/file")
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) $this->config('base_url'))
            ->withToken((string) $this->config('token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) $this->config('timeout', 15));

        if (filled($this->config('customer_code'))) {
            $request = $request->withHeader('CustomerCode', (string) $this->config('customer_code'));
        }

        return $request;
    }

    private function usesSandboxStub(): bool
    {
        return (bool) $this->config('use_stub', true) || blank($this->config('token'));
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? config("services.tochka.{$key}", $default);
    }

    private function sandboxResponse(string $type, string $number, array $data): array
    {
        return [
            'id' => $data['external_id'] ?? 'sandbox-'.$type.'-'.Str::slug($number),
            'status' => 'sent',
            'sandbox' => true,
            'data' => $data,
        ];
    }

    private function invoicePayload(Invoice $invoice): array
    {
        $invoice->loadMissing(['client', 'occurrence.service']);

        return [
            'number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date?->toDateString(),
            'amount' => (string) $invoice->amount,
            'client' => [
                'name' => $invoice->client?->legal_name ?? $invoice->client?->short_name,
                'inn' => $invoice->client?->inn,
                'kpp' => $invoice->client?->kpp,
                'email' => $invoice->client?->invoice_email,
            ],
            'service' => $invoice->occurrence?->service?->document_name ?? $invoice->occurrence?->service?->name,
        ];
    }

    private function actPayload(Act $act): array
    {
        $act->loadMissing(['client', 'invoice', 'occurrence.service']);

        return [
            'number' => $act->act_number,
            'date' => $act->act_date?->toDateString(),
            'amount' => (string) $act->amount,
            'invoice_external_id' => $act->invoice?->external_id,
            'client' => [
                'name' => $act->client?->legal_name ?? $act->client?->short_name,
                'inn' => $act->client?->inn,
                'kpp' => $act->client?->kpp,
            ],
            'service' => $act->occurrence?->service?->document_name ?? $act->occurrence?->service?->name,
        ];
    }
}
