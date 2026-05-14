<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Services\Reports\FinanceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request, FinanceReportService $reports): Response
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);

        $start = isset($validated['date_from'])
            ? Carbon::parse($validated['date_from'])
            : now()->startOfMonth();
        $end = isset($validated['date_to'])
            ? Carbon::parse($validated['date_to'])
            : now()->endOfMonth();

        return Inertia::render('Reports/Index', [
            'filters' => [
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
                'service_id' => $validated['service_id'] ?? '',
                'client_id' => $validated['client_id'] ?? '',
            ],
            'report' => $reports->report(
                $start,
                $end,
                isset($validated['service_id']) ? (int) $validated['service_id'] : null,
                isset($validated['client_id']) ? (int) $validated['client_id'] : null,
            ),
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->orderBy('short_name')->get(['id', 'short_name']),
        ]);
    }
}
