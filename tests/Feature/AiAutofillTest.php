<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAutofillTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_text_can_be_parsed_into_preview_fields(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->postJson(route('clients.ai-autofill'), [
                'text' => 'ООО Ромашка, ИНН 7700000000, КПП 770101001, контакт Иван Петров, телефон +7 999 123-45-67, email billing@example.ru, проект сайт, услуга SEO, бюджет 80000 в месяц',
            ])
            ->assertOk()
            ->assertJsonPath('data.mode', 'preview')
            ->assertJsonPath('data.source', 'stub')
            ->assertJsonPath('data.fields.type', Client::TYPE_LEGAL_ENTITY)
            ->assertJsonPath('data.fields.legal_name', 'ООО Ромашка')
            ->assertJsonPath('data.fields.inn', '7700000000')
            ->assertJsonPath('data.fields.kpp', '770101001')
            ->assertJsonPath('data.fields.invoice_email', 'billing@example.ru')
            ->assertJsonPath('data.fields.contact_person', 'Иван Петров')
            ->assertJsonPath('data.fields.phone', '+7 999 123-45-67');

        $this->assertSame(0, Client::query()->count());
    }

    public function test_client_ai_autofill_requires_enough_text(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->postJson(route('clients.ai-autofill'), [
                'text' => 'коротко',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }
}
