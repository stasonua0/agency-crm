<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ContractorSettlement;
use App\Models\FinancialOperation;
use App\Models\Payee;
use App\Models\PaymentOccurrence;
use App\Models\PayoutBatch;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_settlements_for_one_payee_can_be_batched(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $payee = $this->payeeFixture();
        $settlementA = $this->settlementFixture($payee, 10000);
        $settlementB = $this->settlementFixture($payee, 15000);

        $this->actingAs($user)
            ->post(route('payouts.store'), [
                'settlement_ids' => [$settlementA->id, $settlementB->id],
                'comment' => 'Выплата за май',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payouts.index'));

        $batch = PayoutBatch::query()->firstOrFail();

        $this->assertSame('25000.00', $batch->total_amount);
        $this->assertSame(PayoutBatch::STATUS_PLANNED, $batch->status);
        $this->assertSame(2, $batch->items()->count());
        $this->assertDatabaseHas('payout_batch_items', [
            'payout_batch_id' => $batch->id,
            'contractor_settlement_id' => $settlementA->id,
            'amount_snapshot' => '10000.00',
        ]);
    }

    public function test_batch_requires_one_payee(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $settlementA = $this->settlementFixture($this->payeeFixture('Первый'), 10000);
        $settlementB = $this->settlementFixture($this->payeeFixture('Второй'), 15000);

        $this->actingAs($user)
            ->post(route('payouts.store'), [
                'settlement_ids' => [$settlementA->id, $settlementB->id],
            ])
            ->assertSessionHasErrors('settlement_ids');

        $this->assertSame(0, PayoutBatch::query()->count());
    }

    public function test_planned_batch_can_be_confirmed_once(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $payee = $this->payeeFixture();
        $settlementA = $this->settlementFixture($payee, 10000);
        $settlementB = $this->settlementFixture($payee, 15000);

        $this->actingAs($user)->post(route('payouts.store'), [
            'settlement_ids' => [$settlementA->id, $settlementB->id],
        ]);

        $batch = PayoutBatch::query()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('payouts.mark-paid', $batch), [
                'paid_at' => '2026-05-20',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payouts.index'));

        $this->actingAs($user)
            ->patch(route('payouts.mark-paid', $batch), [
                'paid_at' => '2026-05-20',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payouts.index'));

        $this->assertSame(PayoutBatch::STATUS_PAID, $batch->fresh()->status);
        $this->assertSame(ContractorSettlement::STATUS_PAID, $settlementA->fresh()->status);
        $this->assertSame(ContractorSettlement::STATUS_PAID, $settlementB->fresh()->status);
        $this->assertSame(1, FinancialOperation::query()->where('source', FinancialOperation::SOURCE_PAYOUT_BATCH)->count());
        $this->assertDatabaseHas('financial_operations', [
            'source' => FinancialOperation::SOURCE_PAYOUT_BATCH,
            'source_payout_batch_id' => $batch->id,
            'type' => FinancialOperation::TYPE_EXPENSE,
            'amount' => '25000.00',
            'category' => 'contractor_payout',
        ]);
    }

    private function payeeFixture(string $name = 'Иван Подрядчик'): Payee
    {
        return Payee::create([
            'type' => Payee::TYPE_CONTRACTOR,
            'name' => $name,
            'requisites' => 'Счёт 40702810000000000000',
            'status' => Payee::STATUS_ACTIVE,
        ]);
    }

    private function settlementFixture(Payee $payee, int $amount): ContractorSettlement
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент '.$amount,
            'short_name' => 'Клиент '.$amount,
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = Service::create([
            'name' => 'SEO '.$amount,
            'document_name' => 'SEO '.$amount,
            'status' => Service::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'project-'.$amount,
            'status' => Project::STATUS_ACTIVE,
            'budget' => $amount,
        ]);

        $item = RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => $amount * 4,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => '2026-05-01',
            'next_payment_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'contractor_id' => $payee->id,
            'contractor_amount' => $amount,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        $occurrence = PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => $amount * 4,
            'contractor_amount_snapshot' => $amount,
            'contractor_name_snapshot' => $payee->name,
            'period' => '2026-05-'.$amount,
            'due_date' => '2026-05-01',
            'payment_method' => RecurringItem::METHOD_CASH,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'contractor_id_snapshot' => $payee->id,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);

        $occurrence->markPaid('2026-05-10 12:00:00');

        return ContractorSettlement::query()
            ->where('payment_occurrence_id', $occurrence->id)
            ->firstOrFail();
    }
}
