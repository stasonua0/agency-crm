<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_updated_and_archived(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->post(route('clients.store'), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Ромашка',
                'short_name' => 'Ромашка',
                'inn' => '7700000000',
                'kpp' => '770001001',
                'ogrn' => '1027700000000',
                'legal_address' => 'Москва',
                'invoice_email' => 'billing@example.com',
                'contact_person' => 'Иван',
                'phone' => '+79990000000',
                'comment' => 'Тест',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('clients.index'));

        $client = Client::query()->firstOrFail();

        $this->actingAs($user)
            ->put(route('clients.update', $client), [
                'type' => Client::TYPE_LEGAL_ENTITY,
                'legal_name' => 'ООО Ромашка Плюс',
                'short_name' => 'Ромашка+',
                'inn' => '7700000000',
                'kpp' => '770001001',
                'ogrn' => '1027700000000',
                'legal_address' => 'Москва',
                'invoice_email' => 'billing@example.com',
                'contact_person' => 'Иван',
                'phone' => '+79990000000',
                'comment' => 'Обновлено',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'short_name' => 'Ромашка+',
        ]);

        $this->actingAs($user)
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertSame(Client::STATUS_ARCHIVED, $client->fresh()->status);
        $this->assertNotNull($client->fresh()->archived_at);
    }

    public function test_service_can_be_created_and_archived(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->post(route('services.store'), [
                'name' => 'SEO',
                'document_name' => 'SEO-продвижение',
                'status' => Service::STATUS_ACTIVE,
                'comment' => 'Ежемесячная услуга',
            ])
            ->assertRedirect(route('services.index'));

        $service = Service::query()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertSame(Service::STATUS_ARCHIVED, $service->fresh()->status);
    }

    public function test_project_remaining_amount_is_calculated_and_project_can_be_archived(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);
        $client = Client::create([
            'type' => Client::TYPE_LEGAL_ENTITY,
            'legal_name' => 'ООО Клиент',
            'short_name' => 'Клиент',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'client_id' => $client->id,
                'name' => 'example.com',
                'domain' => 'example.com',
                'status' => Project::STATUS_ACTIVE,
                'budget' => 100000,
                'comment' => 'Тестовый проект',
            ])
            ->assertRedirect(route('projects.index'));

        $project = Project::query()->firstOrFail();

        $this->assertSame(100000.0, $project->remaining_amount);

        $project->forceFill(['paid_amount' => 25000])->save();

        $this->assertSame(75000.0, $project->fresh()->remaining_amount);

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertSame(Project::STATUS_ARCHIVED, $project->fresh()->status);
    }
}
