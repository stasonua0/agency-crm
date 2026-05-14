<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'action']);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->search($request->string('search')->toString())
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AuditLog/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => [
                AuditLog::ACTION_CREATED,
                AuditLog::ACTION_UPDATED,
                AuditLog::ACTION_ARCHIVED,
                AuditLog::ACTION_PAID,
                AuditLog::ACTION_CANCELLED,
                AuditLog::ACTION_CORRECTION,
                AuditLog::ACTION_INVOICE_SENT,
                AuditLog::ACTION_WEBHOOK,
                AuditLog::ACTION_BATCH_PAYOUT,
            ],
        ]);
    }
}
