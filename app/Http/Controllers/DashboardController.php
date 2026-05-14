<?php

namespace App\Http\Controllers;

use App\Services\Reports\FinanceReportService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(FinanceReportService $reports): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => $reports->dashboard(),
        ]);
    }
}
