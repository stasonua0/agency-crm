<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayeeRequest;
use App\Http\Requests\UpdatePayeeRequest;
use App\Models\Payee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayeeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'status']);

        $payees = Payee::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payees/Index', [
            'payees' => $payees,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Payees/Create');
    }

    public function store(StorePayeeRequest $request): RedirectResponse
    {
        Payee::create($request->validated());

        return redirect()->route('payees.index')->with('success', 'Получатель выплат создан.');
    }

    public function edit(Payee $payee): Response
    {
        return Inertia::render('Payees/Edit', [
            'payee' => $payee,
        ]);
    }

    public function update(UpdatePayeeRequest $request, Payee $payee): RedirectResponse
    {
        $payee->update($request->validated());

        return redirect()->route('payees.index')->with('success', 'Получатель выплат обновлён.');
    }

    public function destroy(Payee $payee): RedirectResponse
    {
        $payee->archive();

        return redirect()->route('payees.index')->with('success', 'Получатель выплат архивирован.');
    }
}
