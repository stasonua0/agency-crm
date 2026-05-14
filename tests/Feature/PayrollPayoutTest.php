<?php

namespace Tests\Feature;

use App\Models\FinancialOperation;
use App\Models\Payee;
use App\Models\PayrollPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_payout_can_be_created_updated_and_deleted_when_planned(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $employee = $this->employeeFixture();

        $this->actingAs($user)
            ->post(route('payroll.store'), [
                'employee_id' => $employee->id,
                'amount' => 100000,
                'payout_date' => '2026-05-25',
                'type' => PayrollPayout::TYPE_SALARY,
                'status' => PayrollPayout::STATUS_PLANNED,
                'comment' => 'Май',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payroll.index'));

        $payout = PayrollPayout::query()->firstOrFail();

        $this->assertSame('Иван Сотрудник', $payout->employee_name_snapshot);
        $this->assertSame('Карта 2200', $payout->requisites_snapshot);

        $this->actingAs($user)
            ->put(route('payroll.update', $payout), [
                'employee_id' => $employee->id,
                'amount' => 110000,
                'payout_date' => '2026-05-26',
                'type' => PayrollPayout::TYPE_BONUS,
                'status' => PayrollPayout::STATUS_PLANNED,
                'comment' => 'Бонус',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payroll.index'));

        $this->assertDatabaseHas('payroll_payouts', [
            'id' => $payout->id,
            'amount' => '110000.00',
            'type' => PayrollPayout::TYPE_BONUS,
        ]);

        $this->actingAs($user)
            ->delete(route('payroll.destroy', $payout))
            ->assertRedirect(route('payroll.index'));

        $this->assertSame(0, PayrollPayout::query()->count());
    }

    public function test_paid_payroll_payout_creates_one_financial_operation(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $employee = $this->employeeFixture();

        $this->actingAs($user)
            ->post(route('payroll.store'), [
                'employee_id' => $employee->id,
                'amount' => 100000,
                'payout_date' => '2026-05-25',
                'type' => PayrollPayout::TYPE_SALARY,
                'status' => PayrollPayout::STATUS_PAID,
                'comment' => 'Май',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payroll.index'));

        $payout = PayrollPayout::query()->firstOrFail();
        $payout->markPaid();

        $this->assertSame(PayrollPayout::STATUS_PAID, $payout->fresh()->status);
        $this->assertSame(1, FinancialOperation::query()->where('source', FinancialOperation::SOURCE_PAYROLL)->count());
        $this->assertDatabaseHas('financial_operations', [
            'source' => FinancialOperation::SOURCE_PAYROLL,
            'source_payroll_payout_id' => $payout->id,
            'type' => FinancialOperation::TYPE_EXPENSE,
            'amount' => '100000.00',
            'category' => 'payroll',
        ]);
    }

    public function test_paid_payroll_payout_cannot_be_changed_or_deleted(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $employee = $this->employeeFixture();

        $payout = PayrollPayout::create([
            'employee_id' => $employee->id,
            'employee_name_snapshot' => $employee->name,
            'requisites_snapshot' => $employee->requisites,
            'amount' => 100000,
            'payout_date' => '2026-05-25',
            'type' => PayrollPayout::TYPE_SALARY,
            'status' => PayrollPayout::STATUS_PAID,
        ]);

        $this->actingAs($user)
            ->put(route('payroll.update', $payout), [
                'employee_id' => $employee->id,
                'amount' => 110000,
                'payout_date' => '2026-05-26',
                'type' => PayrollPayout::TYPE_BONUS,
                'status' => PayrollPayout::STATUS_PAID,
                'comment' => 'Попытка',
            ])
            ->assertSessionHasErrors('payroll');

        $this->actingAs($user)
            ->delete(route('payroll.destroy', $payout))
            ->assertSessionHasErrors('payroll');
    }

    public function test_payroll_requires_employee_payee(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $contractor = Payee::create([
            'type' => Payee::TYPE_CONTRACTOR,
            'name' => 'Не сотрудник',
            'status' => Payee::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->post(route('payroll.store'), [
                'employee_id' => $contractor->id,
                'amount' => 100000,
                'payout_date' => '2026-05-25',
                'type' => PayrollPayout::TYPE_SALARY,
                'status' => PayrollPayout::STATUS_PLANNED,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    private function employeeFixture(): Payee
    {
        return Payee::create([
            'type' => Payee::TYPE_EMPLOYEE,
            'name' => 'Иван Сотрудник',
            'requisites' => 'Карта 2200',
            'status' => Payee::STATUS_ACTIVE,
        ]);
    }
}
