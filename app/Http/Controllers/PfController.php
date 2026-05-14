<?php

namespace App\Http\Controllers;

use App\Models\PaymentOccurrence;
use App\Models\AuditLog;
use App\Models\PfPayoutBatch;
use App\Models\RecurringItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Audit\AuditLogger;
use Inertia\Inertia;
use Inertia\Response;

class PfController extends Controller
{
    public function index(): Response
    {
        $occurrences = $this->pfOccurrenceQuery()
            ->where('status', PaymentOccurrence::STATUS_PLANNED)
            ->whereDoesntHave('pfBatchItem')
            ->orderBy('due_date')
            ->get();

        $batches = PfPayoutBatch::query()
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Pf/Index', [
            'occurrences' => $occurrences,
            'batches' => $batches,
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'occurrence_ids' => ['required', 'array', 'min:1'],
            'occurrence_ids.*' => ['integer', 'distinct'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $batch = DB::transaction(function () use ($validated) {
            $occurrences = $this->pfOccurrenceQuery()
                ->whereIn('payment_occurrences.id', $validated['occurrence_ids'])
                ->where('payment_occurrences.status', PaymentOccurrence::STATUS_PLANNED)
                ->lockForUpdate()
                ->get();

            if ($occurrences->count() !== count($validated['occurrence_ids'])) {
                throw ValidationException::withMessages([
                    'occurrence_ids' => 'Часть начислений ПФ не найдена или уже закрыта.',
                ]);
            }

            if ($occurrences->contains(fn (PaymentOccurrence $occurrence) => $occurrence->pfBatchItem()->exists())) {
                throw ValidationException::withMessages([
                    'occurrence_ids' => 'Одно из начислений уже добавлено в пакет ПФ.',
                ]);
            }

            $batch = PfPayoutBatch::create([
                'total_amount' => $occurrences->sum(fn (PaymentOccurrence $occurrence) => (float) $occurrence->amount_snapshot),
                'status' => PfPayoutBatch::STATUS_PLANNED,
                'comment' => $validated['comment'] ?? null,
            ]);

            foreach ($occurrences as $occurrence) {
                $batch->items()->create([
                    'payment_occurrence_id' => $occurrence->id,
                    'amount_snapshot' => $occurrence->amount_snapshot,
                ]);
            }

            return $batch;
        });
        $audit->log(AuditLog::ACTION_BATCH_PAYOUT, $batch, ['type' => 'pf', 'status' => 'created', 'total_amount' => $batch->total_amount]);

        return redirect()->route('pf.index')->with('success', 'Пакет ПФ создан.');
    }

    public function markPaid(Request $request, PfPayoutBatch $pfPayoutBatch, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'paid_at' => ['required', 'date'],
        ]);

        $pfPayoutBatch->markPaid($validated['paid_at']);
        $audit->log(AuditLog::ACTION_BATCH_PAYOUT, $pfPayoutBatch, ['type' => 'pf', 'status' => 'paid', 'paid_at' => $validated['paid_at']]);

        return redirect()->route('pf.index')->with('success', 'Пакет ПФ закрыт.');
    }

    private function pfOccurrenceQuery()
    {
        return PaymentOccurrence::query()
            ->with(['client:id,short_name', 'project:id,name', 'service:id,name,document_name'])
            ->where('operation_type', RecurringItem::TYPE_EXPENSE)
            ->whereHas('service', function ($query) {
                $query
                    ->where('name', 'ilike', 'ПФ')
                    ->orWhere('document_name', 'ilike', 'ПФ');
            });
    }
}
