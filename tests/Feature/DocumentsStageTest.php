<?php

namespace Tests\Feature;

use App\Models\Act;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\StudioSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_settings_can_be_saved(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->put(route('settings.update'), [
                'name' => 'ООО Студия',
                'inn' => '7700000000',
                'kpp' => '770001001',
                'ogrn' => '1027700000000',
                'address' => 'Москва',
                'bank' => 'Тест Банк',
                'checking_account' => '40702810000000000000',
                'correspondent_account' => '30101810000000000000',
                'bik' => '044525000',
                'email' => 'billing@example.com',
                'phone' => '+7 900 000-00-00',
                'vat_enabled' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('studio_settings', [
            'name' => 'ООО Студия',
            'inn' => '7700000000',
            'vat_enabled' => true,
        ]);
        $this->assertSame(1, StudioSetting::query()->count());
    }

    public function test_invoice_can_be_created_for_bank_transfer_occurrence_once(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(RecurringItem::METHOD_BANK_TRANSFER);

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_number' => 'INV-001',
                'invoice_date' => '2026-05-14',
                'status' => Invoice::STATUS_DRAFT,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame('100000.00', $invoice->amount);
        $this->assertSame($invoice->id, $occurrence->fresh()->invoice_id);

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_number' => 'INV-002',
                'invoice_date' => '2026-05-14',
                'status' => Invoice::STATUS_DRAFT,
            ])
            ->assertSessionHasErrors('occurrence_id');
    }

    public function test_invoice_rejects_cash_occurrence(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(RecurringItem::METHOD_CASH);

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_number' => 'INV-CASH',
                'invoice_date' => '2026-05-14',
                'status' => Invoice::STATUS_DRAFT,
            ])
            ->assertSessionHasErrors('occurrence_id');

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_act_can_be_created_for_invoice_occurrence(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(RecurringItem::METHOD_BANK_TRANSFER);
        $invoice = Invoice::create([
            'occurrence_id' => $occurrence->id,
            'client_id' => $occurrence->client_id,
            'invoice_number' => 'INV-001',
            'invoice_date' => '2026-05-14',
            'amount' => $occurrence->amount_snapshot,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->post(route('acts.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_id' => $invoice->id,
                'act_number' => 'ACT-001',
                'act_date' => '2026-05-31',
                'status' => Act::STATUS_AWAITING_SIGNATURE,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('acts.index'));

        $this->assertDatabaseHas('acts', [
            'act_number' => 'ACT-001',
            'invoice_id' => $invoice->id,
            'amount' => '100000.00',
        ]);
    }

    public function test_act_rejects_invoice_from_another_occurrence(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(RecurringItem::METHOD_BANK_TRANSFER, 'A');
        $otherOccurrence = $this->occurrenceFixture(RecurringItem::METHOD_BANK_TRANSFER, 'B');
        $invoice = Invoice::create([
            'occurrence_id' => $otherOccurrence->id,
            'client_id' => $otherOccurrence->client_id,
            'invoice_number' => 'INV-OTHER',
            'invoice_date' => '2026-05-14',
            'amount' => $otherOccurrence->amount_snapshot,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->post(route('acts.store'), [
                'occurrence_id' => $occurrence->id,
                'invoice_id' => $invoice->id,
                'act_number' => 'ACT-BAD',
                'act_date' => '2026-05-31',
                'status' => Act::STATUS_AWAITING_SIGNATURE,
            ])
            ->assertSessionHasErrors('invoice_id');
    }

    private function occurrenceFixture(string $paymentMethod, string $suffix = ''): PaymentOccurrence
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент'.$suffix,
            'short_name' => 'Клиент'.$suffix,
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'name' => 'SEO'.$suffix,
            'document_name' => 'SEO'.$suffix,
            'status' => Service::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'project'.$suffix,
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
            'payment_method' => $paymentMethod,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        return PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => 100000,
            'period' => '2026-05'.$suffix,
            'due_date' => '2026-05-01',
            'payment_method' => $paymentMethod,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
    }
}
