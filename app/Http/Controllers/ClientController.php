<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'type']);

        $clients = Client::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(StoreClientRequest $request, AuditLogger $audit): RedirectResponse
    {
        $client = Client::create($request->validated());
        $audit->log(AuditLog::ACTION_CREATED, $client, ['name' => $client->short_name]);

        return redirect()->route('clients.index')->with('success', 'Клиент создан.');
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validated();
        $before = $client->only(array_keys($validated));
        $client->update($validated);
        $audit->log(AuditLog::ACTION_UPDATED, $client, ['before' => $before, 'after' => $client->only(array_keys($validated))]);

        return redirect()->route('clients.index')->with('success', 'Клиент обновлён.');
    }

    public function destroy(Client $client, AuditLogger $audit): RedirectResponse
    {
        $client->archive();
        $audit->log(AuditLog::ACTION_ARCHIVED, $client, ['name' => $client->short_name]);

        return redirect()->route('clients.index')->with('success', 'Клиент архивирован.');
    }
}
