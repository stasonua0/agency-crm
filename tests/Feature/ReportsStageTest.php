<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FinancialOperation;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_show_cash_basis_summary_and_groupings(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        [$client, $project, $seo, $support] = $this->fixtures();

        $this->operation(FinancialOperation::TYPE_INCOME, 100000, '2026-05-10', $client, $project, $seo);
        $this->operation(FinancialOperation::TYPE_INCOME, 50000, '2026-05-20', $client, $project, $support);
        $this->operation(FinancialOperation::TYPE_EXPENSE, 30000, '2026-05-21', $client, $project, $seo, FinancialOperation::SOURCE_PAYROLL);
        $this->operation(FinancialOperation::TYPE_EXPENSE, 20000, '2026-05-22', $client, $project, $seo, FinancialOperation::SOURCE_PAYOUT_BATCH);
        $this->operation(FinancialOperation::TYPE_EXPENSE, 10000, '2026-05-23', $client, $project, $seo, FinancialOperation::SOURCE_PF_PAYOUT_BATCH);
        $this->operation(FinancialOperation::TYPE_EXPENSE, 5000, '2026-05-24', $client, $project, $seo, FinancialOperation::SOURCE_MANUAL);
        $this->operation(FinancialOperation::TYPE_INCOME, 999999, '2026-06-01', $client, $project, $seo);

        $this->actingAs($user)
            ->get(route('reports.index', [
                'date_from' => '2026-05-01',
                'date_to' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('report.summary.income', 150000)
                ->where('report.summary.expense', 65000)
                ->where('report.summary.profit', 85000)
                ->has('report.income_by_services', 2)
                ->has('report.expenses', 4)
            );
    }

    public function test_dashboard_uses_current_month_cash_metrics_and_expected_payments(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        [$client, $project, $service] = $this->fixtures();

        $this->travelTo('2026-05-14 12:00:00');

        $this->operation(FinancialOperation::TYPE_INCOME, 120000, '2026-05-10', $client, $project, $service);
        $this->operation(FinancialOperation::TYPE_EXPENSE, 40000, '2026-05-11', $client, $project, $service);
        $this->occurrence($client, $project, $service, 70000, '2026-05-20');
        $this->occurrence($client, $project, $service, 90000, '2026-06-05');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('metrics.month_income', 120000)
                ->where('metrics.month_expense', 40000)
                ->where('metrics.profit', 80000)
                ->where('metrics.expected_payments', 70000)
                ->where('metrics.next_month_forecast', 90000)
            );

        $this->travelBack();
    }

    private function fixtures(): array
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Проект',
            'status' => Project::STATUS_ACTIVE,
            'budget' => 200000,
        ]);

        $seo = Service::create([
            'name' => 'SEO',
            'document_name' => 'SEO',
            'status' => Service::STATUS_ACTIVE,
        ]);

        $support = Service::create([
            'name' => 'Поддержка',
            'document_name' => 'Поддержка',
            'status' => Service::STATUS_ACTIVE,
        ]);

        return [$client, $project, $seo, $support];
    }

    private function operation(string $type, int $amount, string $paidAt, Client $client, Project $project, Service $service, string $source = FinancialOperation::SOURCE_MANUAL): FinancialOperation
    {
        return FinancialOperation::create([
            'type' => $type,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'category' => 'report',
            'source' => $source,
            'comment' => 'Отчётный тест',
        ]);
    }

    private function occurrence(Client $client, Project $project, Service $service, int $amount, string $dueDate): PaymentOccurrence
    {
        $item = RecurringItem::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'amount' => $amount,
            'periodicity' => RecurringItem::PERIOD_MONTHLY,
            'start_date' => $dueDate,
            'next_payment_date' => $dueDate,
            'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
            'status' => RecurringItem::STATUS_ACTIVE,
        ]);

        return PaymentOccurrence::create([
            'recurring_item_id' => $item->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'service_id' => $service->id,
            'amount_snapshot' => $amount,
            'period' => substr($dueDate, 0, 7),
            'due_date' => $dueDate,
            'payment_method' => RecurringItem::METHOD_BANK_TRANSFER,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
    }
}
