<?php

namespace App\Console\Commands;

use App\Models\PaymentOccurrence;
use App\Models\RecurringItem;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateOccurrences extends Command
{
    protected $signature = 'crm:generate-occurrences';

    protected $description = 'Создать плановые начисления по активным регулярным операциям.';

    public function handle(): int
    {
        $today = CarbonImmutable::today();
        $created = 0;
        $advanced = 0;

        RecurringItem::query()
            ->where('status', RecurringItem::STATUS_ACTIVE)
            ->whereDate('next_payment_date', '<=', $today)
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($today, &$created, &$advanced) {
                foreach ($items as $item) {
                    DB::transaction(function () use ($item, $today, &$created, &$advanced) {
                        $lockedItem = RecurringItem::query()
                            ->whereKey($item->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedItem || $lockedItem->status !== RecurringItem::STATUS_ACTIVE) {
                            return;
                        }

                        $dueDate = CarbonImmutable::parse($lockedItem->next_payment_date);

                        if ($dueDate->isAfter($today)) {
                            return;
                        }

                        $period = $lockedItem->periodForDate($dueDate);

                        $occurrence = PaymentOccurrence::firstOrCreate(
                            [
                                'recurring_item_id' => $lockedItem->id,
                                'period' => $period,
                            ],
                            [
                                'client_id' => $lockedItem->client_id,
                                'project_id' => $lockedItem->project_id,
                                'service_id' => $lockedItem->service_id,
                                'amount_snapshot' => $lockedItem->amount,
                                'contractor_amount_snapshot' => $lockedItem->contractor_amount,
                                'contractor_name_snapshot' => $lockedItem->contractor_name,
                                'due_date' => $dueDate->toDateString(),
                                'payment_method' => $lockedItem->payment_method,
                                'operation_type' => $lockedItem->operation_type,
                                'status' => PaymentOccurrence::STATUS_PLANNED,
                            ]
                        );

                        if ($occurrence->wasRecentlyCreated) {
                            $created++;
                        }

                        $lockedItem->forceFill([
                            'next_payment_date' => $this->nextPaymentDate($dueDate, $lockedItem->periodicity)->toDateString(),
                        ])->save();

                        $advanced++;
                    });
                }
            });

        $this->info("Начисления созданы: {$created}. Даты следующих платежей обновлены: {$advanced}.");

        return self::SUCCESS;
    }

    private function nextPaymentDate(CarbonImmutable $date, string $periodicity): CarbonImmutable
    {
        return match ($periodicity) {
            RecurringItem::PERIOD_YEARLY => $date->addYearNoOverflow(),
            RecurringItem::PERIOD_SEMIANNUAL => $date->addMonthsNoOverflow(6),
            default => $date->addMonthNoOverflow(),
        };
    }
}
