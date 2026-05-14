<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\StudioSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creation_sends_email_when_client_has_billing_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture('billing@example.com');

        StudioSetting::singleton()->update([
            'invoice_email_subject' => 'Счёт {номер_счёта}',
            'invoice_email_body' => 'Сумма {сумма}, клиент {клиент}, услуга {услуга}',
        ]);

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_number' => 'INV-EMAIL',
                'invoice_date' => '2026-05-14',
                'status' => Invoice::STATUS_DRAFT,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame('billing@example.com', $invoice->email_to);
        $this->assertNotNull($invoice->email_sent_at);
        $this->assertSame('Счёт INV-EMAIL', data_get($invoice->email_raw_response, 'subject'));
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
    }

    public function test_invoice_creation_skips_email_when_client_has_no_billing_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture();

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_number' => 'INV-NO-EMAIL',
                'invoice_date' => '2026-05-14',
                'status' => Invoice::STATUS_DRAFT,
            ])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::query()->firstOrFail();

        $this->assertNull($invoice->email_to);
        $this->assertNull($invoice->email_sent_at);
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
    }

    public function test_invoice_email_can_be_sent_manually(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture('billing@example.com');
        $invoice = $this->invoiceFixture($occurrence);

        $this->actingAs($user)
            ->post(route('invoices.email.store', $invoice))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame('billing@example.com', $invoice->email_to);
        $this->assertNotNull($invoice->email_sent_at);
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
    }

    public function test_invoice_email_requires_client_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture();
        $invoice = $this->invoiceFixture($occurrence);

        $this->actingAs($user)
            ->post(route('invoices.email.store', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->assertNull($invoice->fresh()->email_sent_at);
    }

    private function invoiceFixture(PaymentOccurrence $occurrence): Invoice
    {
        return Invoice::create([
            'occurrence_id' => $occurrence->id,
            'client_id' => $occurrence->client_id,
            'invoice_number' => 'INV-MANUAL',
            'invoice_date' => '2026-05-14',
            'amount' => $occurrence->amount_snapshot,
            'status' => Invoice::STATUS_DRAFT,
        ]);
    }

    private function occurrenceFixture(?string $invoiceEmail = null): PaymentOccurrence
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
            'invoice_email' => $invoiceEmail,
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
