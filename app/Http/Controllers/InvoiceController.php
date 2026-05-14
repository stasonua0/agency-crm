<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\RecurringItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $invoices = Invoice::query()
            ->with(['client:id,short_name', 'occurrence.service:id,name'])
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

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $occurrence = PaymentOccurrence::query()->findOrFail($request->integer('occurrence_id'));

        $invoice = Invoice::create([
            ...$request->validated(),
            'client_id' => $occurrence->client_id,
            'amount' => $occurrence->amount_snapshot,
        ]);

        $occurrence->forceFill(['invoice_id' => $invoice->id])->save();

        return redirect()->route('invoices.index')->with('success', 'Счёт создан.');
    }
}
