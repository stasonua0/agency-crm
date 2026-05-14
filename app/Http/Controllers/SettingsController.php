<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudioSettingRequest;
use App\Models\StudioSetting;
use App\Services\Ai\OpenAiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = StudioSetting::singleton();

        return Inertia::render('Settings/Index', [
            'settings' => [
                ...$settings->toArray(),
                'has_ai_api_key' => $settings->hasAiApiKey(),
            ],
        ]);
    }

    public function update(UpdateStudioSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (blank($validated['ai_api_key'] ?? null)) {
            unset($validated['ai_api_key']);
        }

        if (($validated['ai_provider'] ?? null) === 'stub') {
            $validated['ai_model'] = null;
        }

        StudioSetting::singleton()->update([
            ...$validated,
            'vat_enabled' => $request->boolean('vat_enabled'),
        ]);

        return redirect()->route('settings.index')->with('success', 'Настройки сохранены.');
    }

    public function refreshAiModels(Request $request, OpenAiClient $openAi): RedirectResponse
    {
        $settings = StudioSetting::singleton();
        $apiKey = $request->string('ai_api_key')->toString() ?: $settings->ai_api_key;

        if (blank($apiKey)) {
            return back()->withErrors(['ai_api_key' => 'Сначала укажите OpenAI API-ключ.']);
        }

        try {
            $models = $openAi->listModels($apiKey);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['ai_api_key' => 'Не удалось получить список моделей OpenAI. Проверьте ключ и доступ в интернет.']);
        }

        $settings->update([
            'ai_provider' => 'openai',
            'ai_api_key' => $apiKey,
            'ai_models_cache' => $models,
            'ai_models_synced_at' => now(),
            'ai_model' => $settings->ai_model && in_array($settings->ai_model, $models, true)
                ? $settings->ai_model
                : ($models[0] ?? null),
        ]);

        return redirect()->route('settings.index')->with('success', 'Список моделей OpenAI обновлён.');
    }
}
