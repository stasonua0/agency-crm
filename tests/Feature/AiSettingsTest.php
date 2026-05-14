<?php

namespace Tests\Feature;

use App\Models\StudioSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_refresh_openai_models_and_store_key_encrypted(): void
    {
        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4.1-mini'],
                    ['id' => 'gpt-4.1'],
                ],
            ]),
        ]);

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->post(route('settings.ai-models.refresh'), [
                'ai_api_key' => 'test-openai-key',
            ])
            ->assertRedirect(route('settings.index'));

        $settings = StudioSetting::singleton();

        $this->assertSame('openai', $settings->ai_provider);
        $this->assertSame('test-openai-key', $settings->ai_api_key);
        $this->assertSame(['gpt-4.1', 'gpt-4.1-mini'], $settings->ai_models_cache);
        $this->assertSame('gpt-4.1', $settings->ai_model);
        $this->assertStringNotContainsString('test-openai-key', (string) $settings->getRawOriginal('ai_api_key'));
    }

    public function test_saving_settings_without_key_does_not_clear_existing_key(): void
    {
        $settings = StudioSetting::singleton();
        $settings->update([
            'ai_provider' => 'openai',
            'ai_api_key' => 'existing-key',
            'ai_model' => 'gpt-4.1-mini',
            'ai_models_cache' => ['gpt-4.1-mini'],
        ]);

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->put(route('settings.update'), [
                'name' => 'Студия',
                'vat_enabled' => false,
                'ai_provider' => 'openai',
                'ai_api_key' => '',
                'ai_model' => 'gpt-4.1-mini',
            ])
            ->assertRedirect(route('settings.index'));

        $settings->refresh();

        $this->assertSame('existing-key', $settings->ai_api_key);
        $this->assertSame('gpt-4.1-mini', $settings->ai_model);
    }
}
