<?php

namespace App\Http\Controllers;

use App\Models\PaymentOccurrence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentOccurrenceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'operation_type', 'payment_method']);

        $occurrences = PaymentOccurrence::query()
            ->with(['client:id,short_name,legal_name', 'project:id,name', 'service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('operation_type'), fn ($query) => $query->where('operation_type', $request->string('operation_type')))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')))
            ->orderByDesc('due_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PaymentOccurrences/Index', [
            'occurrences' => $occurrences,
            'filters' => $filters,
        ]);
    }
}
