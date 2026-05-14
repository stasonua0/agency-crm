<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FinancialOperation;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_item_requires_contractor_amount_when_contractor_is_set(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        [$client, $project, $service] = $this->directoryFixture();

        $this->actingAs($user)
            ->post(route('recurring-items.store'), [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'service_id' => $service->id,
                'operation_type' => RecurringItem::TYPE_INCOME,
                'amount' => 100000,
                'periodicity' => RecurringItem::PERIOD_MONTHLY,
                'start_date' => '2026-05-01',
                'next_payment_date' => '2026-05-01',
                'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
                'contractor_name' => 'Подрядчик',
                'contractor_amount' => null,
                'status' => RecurringItem::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('contractor_amount');
    }

    public function test_occurrence_keeps_snapshot_after_recurring_item_changes(): void
    {
        [$client, $project, $service] = $this->directoryFixture();

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
            'contractor_name' => 'Подрядчик',
            'contractor_amount' => 25000,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        $occurrence = PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $item->client_id,
            'project_id' => $item->project_id,
            'service_id' => $item->service_id,
            'amount_snapshot' => $item->amount,
            'contractor_amount_snapshot' => $item->contractor_amount,
            'contractor_name_snapshot' => $item->contractor_name,
            'period' => '2026-05',
            'due_date' => '2026-05-01',
            'payment_method' => $item->payment_method,
            'operation_type' => $item->operation_type,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);

        $item->update([
            'amount' => 120000,
            'contractor_amount' => 30000,
        ]);

        $this->assertSame('100000.00', $occurrence->fresh()->amount_snapshot);
        $this->assertSame('25000.00', $occurrence->fresh()->contractor_amount_snapshot);
    }

    public function test_mark_paid_creates_one_financial_operation(): void
    {
        [$client, $project, $service] = $this->directoryFixture();
        $item = RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => 100000,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-05-01',
            'next_payment_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        $occurrence = PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => 100000,
            'period' => '2026-05',
            'due_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);

        $occurrence->markPaid('2026-05-10 12:00:00');
        $occurrence->markPaid('2026-05-10 12:00:00');

        $this->assertSame(1, FinancialOperation::query()->count());
        $this->assertDatabaseHas('financial_operations', [
            'source' => FinancialOperation::SOURCE_OCCURRENCE,
            'source_occurrence_id' => $occurrence->id,
            'amount' => '100000.00',
        ]);
    }

    public function test_generate_occurrences_command_creates_due_occurrence_once_and_advances_date(): void
    {
        CarbonImmutable::setTestNow('2026-05-14 10:00:00');

        [$client, $project, $service] = $this->directoryFixture();
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
            'contractor_name' => 'Подрядчик',
            'contractor_amount' => 25000,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        $this->artisan('crm:generate-occurrences')->assertSuccessful();
        $this->artisan('crm:generate-occurrences')->assertSuccessful();

        $this->assertSame(1, PaymentOccurrence::query()->count());
        $this->assertDatabaseHas('payment_occurrences', [
            'recurring_item_id' => $item->id,
            'period' => '2026-05',
            'due_date' => '2026-05-01',
            'amount_snapshot' => '100000.00',
            'contractor_amount_snapshot' => '25000.00',
            'contractor_name_snapshot' => 'Подрядчик',
            'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
        $this->assertSame('2026-06-01', $item->fresh()->next_payment_date->toDateString());

        CarbonImmutable::setTestNow();
    }

    public function test_generate_occurrences_command_ignores_stopped_and_future_items(): void
    {
        CarbonImmutable::setTestNow('2026-05-14 10:00:00');

        [$client, $project, $service] = $this->directoryFixture();

        RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => 100000,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-05-01',
            'next_payment_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'status' => RecurringItem::STATUS_STOPPED,
        ]);

        RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => 120000,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-06-01',
            'next_payment_date' => '2026-06-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        $this->artisan('crm:generate-occurrences')->assertSuccessful();

        $this->assertSame(0, PaymentOccurrence::query()->count());

        CarbonImmutable::setTestNow();
    }

    private function directoryFixture(): array
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'name' => 'SEO',
            'document_name' => 'SEO-продвижение',
            'status' => Service::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'example.com',
            'domain' => 'example.com',
            'status' => Project::STATUS_ACTIVE,
            'budget' => 100000,
        ]);

        return [$client, $project, $service];
    }
}
