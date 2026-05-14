<?php

namespace App\Services\CompanyLookup;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class CompanyLookupService
{
    public function findByInn(string $inn): ?array
    {
        $inn = preg_replace('/\D+/', '', $inn) ?? '';

        if (! in_array(strlen($inn), [10, 12], true)) {
            return null;
        }

        if ($this->usesSandboxStub()) {
            return $this->sandboxCompany($inn);
        }

        $response = Http::baseUrl((string) config('services.dadata.base_url'))
            ->withToken((string) config('services.dadata.token'))
            ->withHeaders([
                'X-Secret' => (string) config('services.dadata.secret'),
            ])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.dadata.timeout', 15))
            ->post('/suggestions/api/4_1/rs/findById/party', [
                'query' => $inn,
                'count' => 1,
            ])
            ->throw()
            ->json();

        $suggestion = Arr::first($response['suggestions'] ?? []);

        return $suggestion ? $this->mapDadataSuggestion($suggestion) : null;
    }

    private function usesSandboxStub(): bool
    {
        return (bool) config('services.dadata.sandbox', true)
            || blank(config('services.dadata.token'))
            || blank(config('services.dadata.secret'));
    }

    private function sandboxCompany(string $inn): array
    {
        $isEntrepreneur = strlen($inn) === 12;

        return [
            'type' => $isEntrepreneur ? 'individual_entrepreneur' : 'legal_entity',
            'legal_name' => $isEntrepreneur ? 'ИП Иванов Иван Иванович' : 'ООО Тестовая компания',
            'short_name' => $isEntrepreneur ? 'ИП Иванов И. И.' : 'Тестовая компания',
            'inn' => $inn,
            'kpp' => $isEntrepreneur ? null : '770101001',
            'ogrn' => $isEntrepreneur ? '326770000000000' : '1027700000000',
            'legal_address' => 'г Москва, ул Тестовая, д 1',
            'source' => 'sandbox',
            'raw' => [
                'sandbox' => true,
            ],
        ];
    }

    private function mapDadataSuggestion(array $suggestion): array
    {
        $data = $suggestion['data'] ?? [];
        $opfType = Arr::get($data, 'opf.type');

        return [
            'type' => $opfType === 'INDIVIDUAL' ? 'individual_entrepreneur' : 'legal_entity',
            'legal_name' => Arr::get($data, 'name.full_with_opf') ?? $suggestion['value'] ?? '',
            'short_name' => Arr::get($data, 'name.short_with_opf') ?? $suggestion['value'] ?? '',
            'inn' => Arr::get($data, 'inn'),
            'kpp' => Arr::get($data, 'kpp'),
            'ogrn' => Arr::get($data, 'ogrn'),
            'legal_address' => Arr::get($data, 'address.unrestricted_value') ?? Arr::get($data, 'address.value'),
            'source' => 'dadata',
            'raw' => $suggestion,
        ];
    }
}
