<?php

namespace App\Http\Controllers;

use App\Models\FinancialOperation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialOperationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'source']);

        $operations = FinancialOperation::query()
            ->with(['client:id,short_name,legal_name', 'project:id,name', 'service:id,name'])
            ->search($request->string('search')->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')))
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('FinancialOperations/Index', [
            'operations' => $operations,
            'filters' => $filters,
        ]);
    }
}
