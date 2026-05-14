<?php

namespace App\Services\Reports;

use App\Models\FinancialOperation;
use App\Models\PaymentOccurrence;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceReportService
{
    public function dashboard(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $nextStart = now()->addMonthNoOverflow()->startOfMonth();
        $nextEnd = now()->addMonthNoOverflow()->endOfMonth();

        $income = $this->sumOperations($start, $end, FinancialOperation::TYPE_INCOME);
        $expense = $this->sumOperations($start, $end, FinancialOperation::TYPE_EXPENSE);

        return [
            'month_income' => $income,
            'month_expense' => $expense,
            'profit' => $income - $expense,
            'expected_payments' => (float) PaymentOccurrence::query()
                ->where('operation_type', FinancialOperation::TYPE_INCOME)
                ->where('status', PaymentOccurrence::STATUS_PLANNED)
                ->whereDate('due_date', '<=', $end->toDateString())
                ->sum('amount_snapshot'),
            'next_month_forecast' => (float) PaymentOccurrence::query()
                ->where('operation_type', FinancialOperation::TYPE_INCOME)
                ->where('status', PaymentOccurrence::STATUS_PLANNED)
                ->whereBetween('due_date', [$nextStart->toDateString(), $nextEnd->toDateString()])
                ->sum('amount_snapshot'),
        ];
    }

    public function report(Carbon $start, Carbon $end, ?int $serviceId = null, ?int $clientId = null): array
    {
        $operations = $this->baseOperations($start, $end, $serviceId, $clientId);

        $income = (clone $operations)->where('type', FinancialOperation::TYPE_INCOME)->sum('amount');
        $expense = (clone $operations)->where('type', FinancialOperation::TYPE_EXPENSE)->sum('amount');

        return [
            'summary' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'profit' => (float) $income - (float) $expense,
            ],
            'income_by_services' => $this->incomeByServices($start, $end, $serviceId, $clientId),
            'income_by_months' => $this->incomeByMonths($start, $end, $serviceId, $clientId),
            'expenses' => $this->expenses($start, $end, $serviceId, $clientId),
        ];
    }

    private function incomeByServices(Carbon $start, Carbon $end, ?int $serviceId, ?int $clientId): Collection
    {
        return $this->baseOperations($start, $end, $serviceId, $clientId)
            ->where('type', FinancialOperation::TYPE_INCOME)
            ->leftJoin('services', 'services.id', '=', 'financial_operations.service_id')
            ->selectRaw("coalesce(services.name, 'Без услуги') as label, sum(financial_operations.amount) as amount")
            ->groupBy('label')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'amount' => (float) $row->amount,
            ]);
    }

    private function incomeByMonths(Carbon $start, Carbon $end, ?int $serviceId, ?int $clientId): Collection
    {
        $rows = $this->baseOperations($start, $end, $serviceId, $clientId)
            ->where('type', FinancialOperation::TYPE_INCOME)
            ->selectRaw("to_char(date_trunc('month', paid_at), 'YYYY-MM') as month, sum(amount) as amount")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('amount', 'month');

        return collect(CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth()))
            ->map(fn (Carbon $month) => [
                'label' => $month->format('Y-m'),
                'amount' => (float) ($rows[$month->format('Y-m')] ?? 0),
            ]);
    }

    private function expenses(Carbon $start, Carbon $end, ?int $serviceId, ?int $clientId): Collection
    {
        return $this->baseOperations($start, $end, $serviceId, $clientId)
            ->where('type', FinancialOperation::TYPE_EXPENSE)
            ->selectRaw("
                case
                    when source = ? then 'ЗП'
                    when source = ? then 'Подрядчики'
                    when source = ? then 'ПФ'
                    else 'Прочее'
                end as label,
                sum(amount) as amount
            ", [
                FinancialOperation::SOURCE_PAYROLL,
                FinancialOperation::SOURCE_PAYOUT_BATCH,
                FinancialOperation::SOURCE_PF_PAYOUT_BATCH,
            ])
            ->groupBy('label')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'amount' => (float) $row->amount,
            ]);
    }

    private function baseOperations(Carbon $start, Carbon $end, ?int $serviceId = null, ?int $clientId = null): Builder
    {
        return FinancialOperation::query()
            ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($serviceId, fn (Builder $query) => $query->where('service_id', $serviceId))
            ->when($clientId, fn (Builder $query) => $query->where('client_id', $clientId));
    }

    private function sumOperations(Carbon $start, Carbon $end, string $type): float
    {
        return (float) FinancialOperation::query()
            ->where('type', $type)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
    }
}
