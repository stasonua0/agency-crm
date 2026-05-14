<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FinancialOperation;
use App\Models\PaymentOccurrence;
use App\Models\PfPayoutBatch;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PfPayoutBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pf_occurrences_can_be_batched_and_closed_once(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrenceA = $this->pfOccurrenceFixture(10000);
        $occurrenceB = $this->pfOccurrenceFixture(15000);

        $this->actingAs($user)
            ->post(route('pf.store'), [
                'occurrence_ids' => [$occurrenceA->id, $occurrenceB->id],
                'comment' => 'ПФ май',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pf.index'));

        $batch = PfPayoutBatch::query()->firstOrFail();

        $this->assertSame('25000.00', $batch->total_amount);
        $this->assertSame(2, $batch->items()->count());

        $this->actingAs($user)
            ->patch(route('pf.mark-paid', $batch), [
                'paid_at' => '2026-05-20',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pf.index'));

        $this->actingAs($user)
            ->patch(route('pf.mark-paid', $batch), [
                'paid_at' => '2026-05-20',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pf.index'));

        $this->assertSame(PfPayoutBatch::STATUS_PAID, $batch->fresh()->status);
        $this->assertSame(PaymentOccurrence::STATUS_PAID, $occurrenceA->fresh()->status);
        $this->assertSame(PaymentOccurrence::STATUS_PAID, $occurrenceB->fresh()->status);
        $this->assertSame(1, FinancialOperation::query()->where('source', FinancialOperation::SOURCE_PF_PAYOUT_BATCH)->count());
        $this->assertDatabaseHas('financial_operations', [
            'source' => FinancialOperation::SOURCE_PF_PAYOUT_BATCH,
            'source_pf_payout_batch_id' => $batch->id,
            'type' => FinancialOperation::TYPE_EXPENSE,
            'amount' => '25000.00',
            'category' => 'pf',
        ]);
    }

    public function test_pf_batch_rejects_non_pf_occurrence(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(10000, 'SEO', RecurringItem::TYPE_EXPENSE);

        $this->actingAs($user)
            ->post(route('pf.store'), [
                'occurrence_ids' => [$occurrence->id],
            ])
            ->assertSessionHasErrors('occurrence_ids');

        $this->assertSame(0, PfPayoutBatch::query()->count());
    }

    public function test_pf_batch_rejects_income_occurrence(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->occurrenceFixture(10000, 'ПФ', RecurringItem::TYPE_INCOME);

        $this->actingAs($user)
            ->post(route('pf.store'), [
                'occurrence_ids' => [$occurrence->id],
            ])
            ->assertSessionHasErrors('occurrence_ids');

        $this->assertSame(0, PfPayoutBatch::query()->count());
    }

    private function pfOccurrenceFixture(int $amount): PaymentOccurrence
    {
        return $this->occurrenceFixture($amount, 'ПФ', RecurringItem::TYPE_EXPENSE);
    }

    private function occurrenceFixture(int $amount, string $serviceName, string $operationType): PaymentOccurrence
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент '.$amount.$serviceName.$operationType,
            'short_name' => 'Клиент '.$amount.$serviceName.$operationType,
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'name' => $serviceName,
            'document_name' => $serviceName,
            'status' => Service::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'project-'.$amount.$serviceName.$operationType,
            'status' => Project::STATUS_ACTIVE,
            'budget' => $amount,
        ]);

        $item = RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => $operationType,
            'amount' => $amount,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-05-01',
            'next_payment_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        return PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => $amount,
            'period' => '2026-05-'.$amount.$serviceName.$operationType,
            'due_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'operation_type' => $operationType,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
    }
}
