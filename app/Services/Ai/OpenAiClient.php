<?php

namespace App\Services\Ai;

use App\Models\StudioSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class OpenAiClient
{
    public function listModels(string $apiKey): array
    {
        $response = Http::baseUrl('https://api.openai.com/v1')
            ->withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.ai_autofill.timeout', 30))
            ->get('/models')
            ->throw()
            ->json();

        return collect($response['data'] ?? [])
            ->pluck('id')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    public function parseClientText(string $text, StudioSetting $settings): array
    {
        $response = Http::baseUrl('https://api.openai.com/v1')
            ->withToken((string) $settings->ai_api_key)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.ai_autofill.timeout', 30))
            ->post('/responses', [
                'model' => $settings->ai_model,
                'instructions' => 'Ты извлекаешь данные клиента CRM из русского текста. Верни только JSON по схеме. Не выдумывай реквизиты.',
                'input' => $text,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'crm_client_autofill',
                        'strict' => true,
                        'schema' => $this->clientSchema(),
                    ],
                ],
            ])
            ->throw()
            ->json();

        $decoded = json_decode($this->responseText($response), true);
        $fields = is_array($decoded) ? Arr::get($decoded, 'fields', []) : [];
        $confidence = is_array($decoded) ? (float) Arr::get($decoded, 'confidence', 0.75) : 0.75;

        return [
            'source' => 'openai',
            'mode' => 'preview',
            'confidence' => max(0, min(1, $confidence)),
            'fields' => array_filter($fields, static fn ($value) => filled($value)),
            'notes' => [
                'Это предпросмотр OpenAI. Данные попадут в форму только после нажатия “Применить”.',
                'Реквизиты по ИНН лучше дополнительно проверить через DaData.',
            ],
        ];
    }

    private function responseText(array $response): string
    {
        if (filled($response['output_text'] ?? null)) {
            return (string) $response['output_text'];
        }

        foreach ($response['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (filled($content['text'] ?? null)) {
                    return (string) $content['text'];
                }
            }
        }

        return '{}';
    }

    private function clientSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['confidence', 'fields'],
            'properties' => [
                'confidence' => ['type' => 'number'],
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'type',
                        'legal_name',
                        'short_name',
                        'inn',
                        'kpp',
                        'ogrn',
                        'legal_address',
                        'invoice_email',
                        'contact_person',
                        'phone',
                        'comment',
                        'status',
                    ],
                    'properties' => [
                        'type' => ['type' => ['string', 'null'], 'enum' => ['legal_entity', 'individual_entrepreneur', 'individual', null]],
                        'legal_name' => ['type' => ['string', 'null']],
                        'short_name' => ['type' => ['string', 'null']],
                        'inn' => ['type' => ['string', 'null']],
                        'kpp' => ['type' => ['string', 'null']],
                        'ogrn' => ['type' => ['string', 'null']],
                        'legal_address' => ['type' => ['string', 'null']],
                        'invoice_email' => ['type' => ['string', 'null']],
                        'contact_person' => ['type' => ['string', 'null']],
                        'phone' => ['type' => ['string', 'null']],
                        'comment' => ['type' => ['string', 'null']],
                        'status' => ['type' => ['string', 'null'], 'enum' => ['active', 'archived', null]],
                    ],
                ],
            ],
        ];
    }
}
