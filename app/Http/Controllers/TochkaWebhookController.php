<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\TochkaWebhookEvent;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TochkaWebhookController extends Controller
{
    public function __invoke(Request $request, AuditLogger $audit): JsonResponse
    {
        $payload = $request->json()->all() ?: $request->all();
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $request->getContent();
        $hash = hash('sha256', $raw);
        $eventId = $this->eventId($request, $payload);

        if (
            TochkaWebhookEvent::query()->where('payload_hash', $hash)->exists()
            || ($eventId && TochkaWebhookEvent::query()->where('event_id', $eventId)->exists())
        ) {
            return response()->json(['status' => TochkaWebhookEvent::STATUS_DUPLICATE]);
        }

        $event = TochkaWebhookEvent::create([
            'event_id' => $eventId,
            'payload_hash' => $hash,
            'external_id' => $this->externalId($payload),
            'status' => TochkaWebhookEvent::STATUS_RECEIVED,
            'payload' => $payload,
        ]);
        $audit->log(AuditLog::ACTION_WEBHOOK, $event, ['external_id' => $event->external_id, 'event_id' => $event->event_id]);

        DB::transaction(function () use ($event, $payload) {
            $invoice = Invoice::query()
                ->with('occurrence')
                ->where('external_id', $event->external_id)
                ->first();

            if (! $invoice) {
                $this->markRequiresAttention($event, 'Счёт по external_id не найден.');

                return;
            }

            $amount = $this->amount($payload);

            if ($amount !== null && $this->money($invoice->amount) !== $this->money($amount)) {
                $this->markRequiresAttention($event, 'Сумма webhook не совпадает со счётом.');

                return;
            }

            if ($this->isPaid($payload)) {
                if ($invoice->occurrence && $invoice->occurrence->status !== $invoice->occurrence::STATUS_PAID) {
                    $invoice->occurrence->markPaid($this->paidAt($payload));
                }

                $invoice->update(['status' => Invoice::STATUS_PAID]);
            }

            $event->update([
                'status' => TochkaWebhookEvent::STATUS_PROCESSED,
                'message' => 'Webhook обработан.',
                'processed_at' => now(),
            ]);
        });

        return response()->json(['status' => $event->fresh()->status]);
    }

    private function eventId(Request $request, array $payload): ?string
    {
        return $request->header('X-Webhook-Id')
            ?? Arr::get($payload, 'event_id')
            ?? Arr::get($payload, 'id');
    }

    private function externalId(array $payload): ?string
    {
        return Arr::get($payload, 'data.external_id')
            ?? Arr::get($payload, 'data.invoice_id')
            ?? Arr::get($payload, 'invoice.external_id')
            ?? Arr::get($payload, 'external_id');
    }

    private function amount(array $payload): ?string
    {
        return Arr::get($payload, 'data.amount')
            ?? Arr::get($payload, 'invoice.amount')
            ?? Arr::get($payload, 'amount');
    }

    private function paidAt(array $payload): Carbon
    {
        $paidAt = Arr::get($payload, 'data.paid_at')
            ?? Arr::get($payload, 'paid_at')
            ?? Arr::get($payload, 'created_at');

        return $paidAt ? Carbon::parse($paidAt) : now();
    }

    private function isPaid(array $payload): bool
    {
        $status = strtolower((string) (Arr::get($payload, 'data.status') ?? Arr::get($payload, 'status')));
        $event = strtolower((string) (Arr::get($payload, 'event') ?? Arr::get($payload, 'type')));

        return in_array($status, ['paid', 'success', 'completed'], true)
            || in_array($event, ['invoice.paid', 'payment.paid'], true);
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function markRequiresAttention(TochkaWebhookEvent $event, string $message): void
    {
        $event->update([
            'status' => TochkaWebhookEvent::STATUS_REQUIRES_ATTENTION,
            'message' => $message,
            'processed_at' => now(),
        ]);
    }
}
