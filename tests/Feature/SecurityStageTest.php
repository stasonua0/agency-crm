<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FinancialOperation;
use App\Models\Payee;
use App\Models\PayrollPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_open_read_pages_but_cannot_mutate(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get(route('clients.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('clients.store'), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Запрет',
                'short_name' => 'Запрет',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->assertSame(0, Client::query()->count());
    }

    public function test_finance_manager_can_mutate_finance_but_cannot_update_settings(): void
    {
        $finance = User::factory()->create(['role' => User::ROLE_FINANCE_MANAGER]);

        $this->actingAs($finance)
            ->post(route('clients.store'), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Клиент',
                'short_name' => 'Клиент',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($finance)
            ->put(route('settings.update'), [
                'name' => 'ООО Студия',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_update_settings(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)
            ->put(route('settings.update'), [
                'name' => 'ООО Студия',
                'vat_enabled' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.index'));
    }

    public function test_payroll_paid_action_is_idempotent(): void
    {
        $employee = Payee::create([
            'type' => Payee::TYPE_EMPLOYEE,
            'name' => 'Сотрудник',
            'status' => Payee::STATUS_ACTIVE,
        ]);

        $payout = PayrollPayout::create([
            'employee_id' => $employee->id,
            'employee_name_snapshot' => $employee->name,
            'amount' => 50000,
            'payout_date' => '2026-05-14',
            'type' => PayrollPayout::TYPE_SALARY,
            'status' => PayrollPayout::STATUS_PLANNED,
        ]);

        $payout->markPaid();
        $payout->markPaid();

        $this->assertSame(1, FinancialOperation::query()->where('source_payroll_payout_id', $payout->id)->count());
    }
}
