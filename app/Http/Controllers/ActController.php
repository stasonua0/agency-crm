<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActRequest;
use App\Models\Act;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $acts = Act::query()
            ->with(['client:id,short_name', 'invoice:id,invoice_number', 'occurrence.service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('act_date')
            ->paginate(10)
            ->withQueryString();

        $occurrences = PaymentOccurrence::query()
            ->with(['client:id,short_name', 'project:id,name', 'service:id,name', 'invoice:id,occurrence_id,invoice_number'])
            ->orderByDesc('due_date')
            ->get();

        return Inertia::render('Acts/Index', [
            'acts' => $acts,
            'occurrences' => $occurrences,
            'invoices' => Invoice::query()->orderByDesc('invoice_date')->get(['id', 'occurrence_id', 'invoice_number']),
            'filters' => $filters,
        ]);
    }

    public function store(StoreActRequest $request): RedirectResponse
    {
        $occurrence = PaymentOccurrence::query()->findOrFail($request->integer('occurrence_id'));

        Act::create([
            ...$request->validated(),
            'client_id' => $occurrence->client_id,
            'amount' => $occurrence->amount_snapshot,
        ]);

        return redirect()->route('acts.index')->with('success', 'Акт создан.');
    }
}
