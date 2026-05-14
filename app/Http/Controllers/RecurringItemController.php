<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringItemRequest;
use App\Http\Requests\UpdateRecurringItemRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringItemController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'operation_type', 'payment_method']);

        $items = RecurringItem::query()
            ->with(['client:id,short_name,legal_name', 'project:id,name', 'service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('operation_type'), fn ($query) => $query->where('operation_type', $request->string('operation_type')))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('RecurringItems/Index', [
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('RecurringItems/Create', $this->formOptions());
    }

    public function store(StoreRecurringItemRequest $request): RedirectResponse
    {
        RecurringItem::create($request->validated());

        return redirect()->route('recurring-items.index')->with('success', 'Регулярная операция создана.');
    }

    public function edit(RecurringItem $recurringItem): Response
    {
        return Inertia::render('RecurringItems/Edit', [
            ...$this->formOptions(),
            'item' => $recurringItem,
        ]);
    }

    public function update(UpdateRecurringItemRequest $request, RecurringItem $recurringItem): RedirectResponse
    {
        $recurringItem->update($request->validated());

        return redirect()->route('recurring-items.index')->with('success', 'Регулярная операция обновлена.');
    }

    public function destroy(RecurringItem $recurringItem): RedirectResponse
    {
        $recurringItem->forceFill(['status' => RecurringItem::STATUS_STOPPED])->save();

        return redirect()->route('recurring-items.index')->with('success', 'Регулярная операция остановлена.');
    }

    private function formOptions(): array
    {
        return [
            'clients' => Client::query()
                ->where('status', Client::STATUS_ACTIVE)
                ->orderBy('short_name')
                ->get(['id', 'short_name', 'legal_name']),
            'projects' => Project::query()
                ->whereIn('status', [Project::STATUS_ACTIVE, Project::STATUS_PAUSED])
                ->orderBy('name')
                ->get(['id', 'client_id', 'name']),
            'services' => Service::query()
                ->where('status', Service::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name', 'document_name']),
        ];
    }
}
