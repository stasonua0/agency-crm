<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\RecurringItem;
use App\Services\Invoices\InvoiceEmailService;
use App\Services\Audit\AuditLogger;
use App\Services\Tochka\TochkaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $invoices = Invoice::query()
            ->with(['client:id,short_name,invoice_email', 'occurrence.service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        $occurrences = PaymentOccurrence::query()
            ->with(['client:id,short_name', 'project:id,name', 'service:id,name'])
            ->where('payment_method', RecurringItem::METHOD_BANK_TRANSFER)
            ->whereDoesntHave('invoice')
            ->orderByDesc('due_date')
            ->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'occurrences' => $occurrences,
            'filters' => $filters,
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceEmailService $email, AuditLogger $audit): RedirectResponse
    {
        $occurrence = PaymentOccurrence::query()->findOrFail($request->integer('occurrence_id'));

        $invoice = DB::transaction(function () use ($request, $occurrence) {
            $invoice = Invoice::create([
                ...$request->validated(),
                'client_id' => $occurrence->client_id,
                'amount' => $occurrence->amount_snapshot,
            ]);

            $occurrence->forceFill(['invoice_id' => $invoice->id])->save();

            return $invoice;
        });

        $sent = $email->send($invoice);
        $audit->log(AuditLog::ACTION_CREATED, $invoice, ['invoice_number' => $invoice->invoice_number, 'amount' => $invoice->amount]);
        if ($sent) {
            $audit->log(AuditLog::ACTION_INVOICE_SENT, $invoice, ['email_to' => $invoice->fresh()->email_to]);
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', $sent ? 'Счёт создан и отправлен по email.' : 'Счёт создан. Email клиента не указан.');
    }

    public function sendEmail(Invoice $invoice, InvoiceEmailService $email, AuditLogger $audit): RedirectResponse
    {
        $email->sendOrFail($invoice);
        $audit->log(AuditLog::ACTION_INVOICE_SENT, $invoice, ['email_to' => $invoice->fresh()->email_to]);

        return back()->with('success', 'Счёт отправлен по email.');
    }

    public function sendTochka(Invoice $invoice, TochkaClient $tochka, InvoiceEmailService $email, AuditLogger $audit): RedirectResponse
    {
        if ($invoice->status === Invoice::STATUS_PAID || $invoice->status === Invoice::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'invoice' => 'Оплаченный или отменённый счёт нельзя повторно отправить в Точку.',
            ]);
        }

        if ($invoice->external_id) {
            return back()->with('success', 'Счёт уже создан в Точке.');
        }

        DB::transaction(function () use ($invoice, $tochka) {
            $invoice->loadMissing(['client', 'occurrence.service']);

            $createResponse = $tochka->createInvoice($invoice);
            $externalId = data_get($createResponse, 'data.external_id')
                ?? data_get($createResponse, 'id');

            if (! $externalId) {
                throw ValidationException::withMessages([
                    'invoice' => 'Точка не вернула external_id счёта.',
                ]);
            }

            $fileResponse = $tochka->getInvoiceFile($externalId);

            $invoice->update([
                'external_id' => $externalId,
                'invoice_number' => data_get($createResponse, 'data.invoice_number', $invoice->invoice_number),
                'invoice_url' => data_get($createResponse, 'data.invoice_url') ?? data_get($fileResponse, 'url'),
                'invoice_pdf_path' => data_get($fileResponse, 'path'),
                'status' => Invoice::STATUS_SENT,
                'raw_response' => [
                    'create' => $createResponse,
                    'file' => $fileResponse,
                ],
            ]);
        });

        $email->send($invoice->fresh());
        $audit->log(AuditLog::ACTION_INVOICE_SENT, $invoice->fresh(), ['channel' => 'tochka', 'external_id' => $invoice->fresh()->external_id]);

        return back()->with('success', 'Счёт создан в sandbox Точки.');
    }
}
