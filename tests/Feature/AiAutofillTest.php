<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\StudioSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_client_text_can_be_parsed_with_openai_when_configured(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'confidence' => 0.91,
                    'fields' => [
                        'type' => Client::TYPE_LEGAL_ENTITY,
                        'legal_name' => 'ООО Ромашка',
                        'short_name' => 'Ромашка',
                        'inn' => '7700000000',
                        'kpp' => '770101001',
                        'ogrn' => null,
                        'legal_address' => null,
                        'invoice_email' => 'billing@example.ru',
                        'contact_person' => 'Иван Петров',
                        'phone' => '+7 999 123-45-67',
                        'comment' => null,
                        'status' => Client::STATUS_ACTIVE,
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        StudioSetting::singleton()->update([
            'ai_provider' => 'openai',
            'ai_api_key' => 'test-openai-key',
            'ai_model' => 'gpt-4.1-mini',
        ]);

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->postJson(route('clients.ai-autofill'), [
                'text' => 'ООО Ромашка, ИНН 7700000000, контакт Иван Петров',
            ])
            ->assertOk()
            ->assertJsonPath('data.source', 'openai')
            ->assertJsonPath('data.confidence', 0.91)
            ->assertJsonPath('data.fields.legal_name', 'ООО Ромашка')
            ->assertJsonPath('data.fields.invoice_email', 'billing@example.ru');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/responses'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $request['model'] === 'gpt-4.1-mini');
    }
}
