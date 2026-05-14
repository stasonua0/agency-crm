<?php

namespace App\Http\Controllers;

use App\Models\ContractorSettlement;
use App\Models\AuditLog;
use App\Models\PayoutBatch;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function index(): Response
    {
        $settlements = ContractorSettlement::query()
            ->with(['payee:id,name', 'occurrence.client:id,short_name', 'occurrence.project:id,name', 'occurrence.service:id,name'])
            ->where('status', ContractorSettlement::STATUS_PENDING)
            ->whereNotNull('payee_id')
            ->orderBy('payee_name_snapshot')
            ->orderBy('created_at')
            ->get();

        $batches = PayoutBatch::query()
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payouts/Index', [
            'settlements' => $settlements,
            'batches' => $batches,
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'settlement_ids' => ['required', 'array', 'min:1'],
            'settlement_ids.*' => ['integer', 'distinct'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $batch = DB::transaction(function () use ($validated) {
            $settlements = ContractorSettlement::query()
                ->whereIn('id', $validated['settlement_ids'])
                ->lockForUpdate()
                ->get();

            if ($settlements->count() !== count($validated['settlement_ids'])) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'Часть выплат не найдена.',
                ]);
            }

            if ($settlements->contains(fn (ContractorSettlement $settlement) => $settlement->status !== ContractorSettlement::STATUS_PENDING)) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'В пакет можно добавить только pending-выплаты.',
                ]);
            }

            if ($settlements->contains(fn (ContractorSettlement $settlement) => blank($settlement->payee_id))) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'Для пакетной выплаты нужен получатель из справочника.',
                ]);
            }

            if ($settlements->pluck('payee_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'В одном пакете может быть только один получатель.',
                ]);
            }

            $first = $settlements->first();
            $total = $settlements->sum(fn (ContractorSettlement $settlement) => (float) $settlement->amount);

            $batch = PayoutBatch::create([
                'payee_id' => $first->payee_id,
                'payee_name_snapshot' => $first->payee_name_snapshot,
                'payee_requisites_snapshot' => $first->payee_requisites_snapshot,
                'total_amount' => $total,
                'status' => PayoutBatch::STATUS_PLANNED,
                'comment' => $validated['comment'] ?? null,
            ]);

            foreach ($settlements as $settlement) {
                $batch->items()->create([
                    'contractor_settlement_id' => $settlement->id,
                    'amount_snapshot' => $settlement->amount,
                ]);
            }

            return $batch;
        });
        $audit->log(AuditLog::ACTION_BATCH_PAYOUT, $batch, ['status' => 'created', 'total_amount' => $batch->total_amount]);

        return redirect()->route('payouts.index')->with('success', 'Пакет выплат создан.');
    }

    public function markPaid(Request $request, PayoutBatch $payoutBatch, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'paid_at' => ['required', 'date'],
        ]);

        $payoutBatch->markPaid($validated['paid_at']);
        $audit->log(AuditLog::ACTION_BATCH_PAYOUT, $payoutBatch, ['status' => 'paid', 'paid_at' => $validated['paid_at']]);

        return redirect()->route('payouts.index')->with('success', 'Пакет выплат подтверждён.');
    }
}
