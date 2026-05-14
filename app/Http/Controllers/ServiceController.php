<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
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

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()->route('services.index')->with('success', 'Услуга создана.');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Services/Edit', [
            'service' => $service,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')->with('success', 'Услуга обновлена.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->archive();

        return redirect()->route('services.index')->with('success', 'Услуга архивирована.');
    }
}
