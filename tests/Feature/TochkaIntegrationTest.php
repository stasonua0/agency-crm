<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FinancialOperation;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\TochkaWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TochkaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_in_tochka_sandbox_once(): void
    {
        config(['services.tochka.sandbox' => true]);

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture();
        $invoice = $this->invoiceFixture($occurrence);

        $this->actingAs($user)
            ->post(route('invoices.tochka.store', $invoice))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertSame('sandbox-invoice-'.$invoice->id, $invoice->external_id);
        $this->assertNotNull($invoice->invoice_url);
        $this->assertSame('sandbox-invoice-'.$invoice->id, data_get($invoice->raw_response, 'create.data.external_id'));

        $this->actingAs($user)
            ->post(route('invoices.tochka.store', $invoice))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Invoice::query()->whereNotNull('external_id')->count());
    }

    public function test_tochka_paid_webhook_marks_occurrence_paid_once(): void
    {
        $occurrence = $this->occurrenceFixture();
        $invoice = $this->invoiceFixture($occurrence, [
            'external_id' => 'tochka-123',
            'status' => Invoice::STATUS_SENT,
        ]);

        $payload = [
            'event_id' => 'evt-1',
            'event' => 'invoice.paid',
            'data' => [
                'external_id' => 'tochka-123',
                'amount' => '100000.00',
                'status' => 'paid',
                'paid_at' => '2026-05-14 12:00:00',
            ],
        ];

        $this->postJson(route('webhooks.tochka'), $payload)
            ->assertOk()
            ->assertJson(['status' => TochkaWebhookEvent::STATUS_PROCESSED]);

        $this->assertSame(PaymentOccurrence::STATUS_PAID, $occurrence->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(1, FinancialOperation::query()->where('source_occurrence_id', $occurrence->id)->count());

        $this->postJson(route('webhooks.tochka'), $payload)
            ->assertOk()
            ->assertJson(['status' => TochkaWebhookEvent::STATUS_DUPLICATE]);

        $this->assertSame(1, FinancialOperation::query()->where('source_occurrence_id', $occurrence->id)->count());
    }

    public function test_tochka_webhook_requires_attention_when_invoice_not_found(): void
    {
        $this->postJson(route('webhooks.tochka'), [
            'event_id' => 'evt-missing',
            'event' => 'invoice.paid',
            'data' => [
                'external_id' => 'missing-invoice',
                'amount' => '100000.00',
                'status' => 'paid',
            ],
        ])
            ->assertOk()
            ->assertJson(['status' => TochkaWebhookEvent::STATUS_REQUIRES_ATTENTION]);

        $this->assertDatabaseHas('tochka_webhook_events', [
            'external_id' => 'missing-invoice',
            'status' => TochkaWebhookEvent::STATUS_REQUIRES_ATTENTION,
        ]);
    }

    public function test_tochka_webhook_requires_attention_when_amount_differs(): void
    {
        $occurrence = $this->occurrenceFixture();
        $this->invoiceFixture($occurrence, [
            'external_id' => 'tochka-amount',
            'status' => Invoice::STATUS_SENT,
        ]);

        $this->postJson(route('webhooks.tochka'), [
            'event_id' => 'evt-amount',
            'event' => 'invoice.paid',
            'data' => [
                'external_id' => 'tochka-amount',
                'amount' => '999.00',
                'status' => 'paid',
            ],
        ])
            ->assertOk()
            ->assertJson(['status' => TochkaWebhookEvent::STATUS_REQUIRES_ATTENTION]);

        $this->assertSame(PaymentOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame(0, FinancialOperation::query()->count());
    }

    private function invoiceFixture(PaymentOccurrence $occurrence, array $overrides = []): Invoice
    {
        return Invoice::create([
            'occurrence_id' => $occurrence->id,
            'client_id' => $occurrence->client_id,
            'invoice_number' => $overrides['invoice_number'] ?? 'INV-001',
            'invoice_date' => $overrides['invoice_date'] ?? '2026-05-14',
            'amount' => $occurrence->amount_snapshot,
            'status' => $overrides['status'] ?? Invoice::STATUS_DRAFT,
            'external_id' => $overrides['external_id'] ?? null,
        ]);
    }

    private function occurrenceFixture(): PaymentOccurrence
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
            'inn' => '7700000000',
            'invoice_email' => 'billing@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'name' => 'SEO',
            'document_name' => 'SEO',
            'status' => Service::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Проект',
            'status' => Project::STATUS_ACTIVE,
            'budget' => 100000,
        ]);

        $item = RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => 100000,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-05-01',
            'next_payment_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        return PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => 100000,
            'period' => '2026-05',
            'due_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
    }
}
