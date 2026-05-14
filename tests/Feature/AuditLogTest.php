<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\PaymentOccurrence;
use App\Models\Project;
use App\Models\RecurringItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_create_and_update_are_logged(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->post(route('clients.store'), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Клиент',
                'short_name' => 'Клиент',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors();

        $client = Client::query()->firstOrFail();

        $this->actingAs($user)
            ->put(route('clients.update', $client), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Клиент',
                'short_name' => 'Клиент новый',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_CREATED,
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_UPDATED,
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    public function test_payment_and_correction_are_logged(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $occurrence = $this->cashOccurrence();

        $this->actingAs($user)
            ->patch(route('payment.occurrences.mark-paid', $occurrence), [
                'paid_at' => '2026-05-14',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('payment.occurrences.corrections.store', $occurrence->fresh()), [
                'type' => 'income',
                'amount' => 1000,
                'paid_at' => '2026-05-15',
                'comment' => 'Корректировка',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PAID,
            'auditable_type' => PaymentOccurrence::class,
            'auditable_id' => $occurrence->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CORRECTION,
            'auditable_type' => PaymentOccurrence::class,
            'auditable_id' => $occurrence->id,
        ]);
    }

    public function test_audit_log_is_read_only(): void
    {
        $log = AuditLog::create(['action' => AuditLog::ACTION_CREATED]);

        $this->expectException(LogicException::class);
        $log->update(['action' => AuditLog::ACTION_UPDATED]);
    }

    public function test_audit_log_page_can_be_opened(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        AuditLog::create(['action' => AuditLog::ACTION_CREATED]);

        $this->actingAs($user)
            ->get(route('audit.log.index'))
            ->assertOk();
    }

    private function cashOccurrence(): PaymentOccurrence
    {
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
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
            'payment_method' => RecurringItem::METHOD_CASH,
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
            'payment_method' => RecurringItem::METHOD_CASH,
            'operation_type' => RecurringItem::TYPE_INCOME,
            'status' => PaymentOccurrence::STATUS_PLANNED,
        ]);
    }
}
