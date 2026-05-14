<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\AuditLog;
use App\Models\Service;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        $services = Service::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Services/Create');
    }

    public function store(StoreServiceRequest $request, AuditLogger $audit): RedirectResponse
    {
        $service = Service::create($request->validated());
        $audit->log(AuditLog::ACTION_CREATED, $service, ['name' => $service->name]);

        return redirect()->route('services.index')->with('success', 'Услуга создана.');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Services/Edit', [
            'service' => $service,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validated();
        $before = $service->only(array_keys($validated));
        $service->update($validated);
        $audit->log(AuditLog::ACTION_UPDATED, $service, ['before' => $before, 'after' => $service->only(array_keys($validated))]);

        return redirect()->route('services.index')->with('success', 'Услуга обновлена.');
    }

    public function destroy(Service $service, AuditLogger $audit): RedirectResponse
    {
        $service->archive();
        $audit->log(AuditLog::ACTION_ARCHIVED, $service, ['name' => $service->name]);

        return redirect()->route('services.index')->with('success', 'Услуга архивирована.');
    }
}
