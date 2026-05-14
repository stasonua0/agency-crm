<?php

namespace App\Services\Ai;

use App\Models\StudioSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiAutofillService
{
    public function __construct(
        private readonly OpenAiClient $openAi,
    ) {
    }

    public function parseClientText(string $text): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        $settings = StudioSetting::singleton();

        if ($settings->ai_provider === 'openai' && filled($settings->ai_api_key) && filled($settings->ai_model)) {
            try {
                return $this->openAi->parseClientText($text, $settings);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $this->stubParseClientText($text);
    }

    public function stubParseClientText(string $text): array
    {
        return [
            'source' => 'stub',
            'mode' => 'preview',
            'confidence' => $this->confidence($text),
            'fields' => array_filter([
                'type' => $this->clientType($text),
                'legal_name' => $this->legalName($text),
                'short_name' => $this->shortName($text),
                'inn' => $this->matchValue($text, '/(?:^|[^\d])(?:инн\s*[:№-]?\s*)?(\d{10}|\d{12})(?:[^\d]|$)/iu'),
                'kpp' => $this->matchValue($text, '/кпп\s*[:№-]?\s*(\d{9})/iu'),
                'ogrn' => $this->matchValue($text, '/огрн(?:ип)?\s*[:№-]?\s*(\d{13}|\d{15})/iu'),
                'legal_address' => $this->phraseAfter($text, ['юридический адрес', 'адрес']),
                'invoice_email' => $this->matchValue($text, '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', 0),
                'contact_person' => $this->phraseAfter($text, ['контактное лицо', 'контакт', 'менеджер']),
                'phone' => $this->phone($text),
                'comment' => $this->comment($text),
                'status' => 'active',
            ], static fn ($value) => filled($value)),
            'notes' => [
                'Это предпросмотр. Данные попадут в форму только после нажатия “Применить”.',
                'Реквизиты по ИНН лучше дополнительно проверить через DaData.',
            ],
        ];
    }

    private function confidence(string $text): float
    {
        $signals = 0;

        foreach (['инн', 'ооо', 'ип', '@', 'тел', 'контакт'] as $signal) {
            $signals += Str::contains(Str::lower($text), $signal) ? 1 : 0;
        }

        return min(0.95, 0.35 + ($signals * 0.1));
    }

    private function clientType(string $text): string
    {
        $lower = Str::lower($text);

        if (preg_match('/\bип\b/iu', $lower)) {
            return 'individual_entrepreneur';
        }

        if (preg_match('/\b(ооо|ао|пао|зао)\b/iu', $lower)) {
            return 'legal_entity';
        }

        return 'legal_entity';
    }

    private function legalName(string $text): ?string
    {
        if (preg_match('/\b(ооо|ао|пао|зао)\s+[«"]?([^,;"]+?)[»"]?(?=\s+(?:инн|кпп|огрн|адрес|контакт|тел|email)\b|[,;]|$)/iu', $text, $matches)) {
            return Str::upper($matches[1]).' '.trim($matches[2], " «»\"");
        }

        if (preg_match('/\bип\s+([^,;]+?)(?=\s+(?:инн|огрнип|адрес|контакт|тел|email)\b|[,;]|$)/iu', $text, $matches)) {
            return 'ИП '.trim($matches[1], " «»\"");
        }

        return $this->phraseAfter($text, ['клиент', 'компания']);
    }

    private function shortName(string $text): ?string
    {
        $legalName = $this->legalName($text);

        if (! $legalName) {
            return null;
        }

        if (preg_match('/[«"]([^»"]+)[»"]/u', $legalName, $matches)) {
            return $matches[1];
        }

        return preg_replace('/^(ООО|АО|ПАО|ЗАО|ИП)\s+/u', '', $legalName) ?: $legalName;
    }

    private function phone(string $text): ?string
    {
        if (preg_match('/(?:тел(?:ефон)?|phone)?\s*[:№-]?\s*((?:\+7|8)[\d\s()\-]{7,}\d)/iu', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function comment(string $text): ?string
    {
        $comment = $this->phraseAfter($text, ['комментарий', 'примечание']);

        if ($comment) {
            return $comment;
        }

        $parts = [];

        if ($service = $this->phraseAfter($text, ['услуга'])) {
            $parts[] = 'Услуга: '.$service;
        }

        if ($project = $this->phraseAfter($text, ['проект'])) {
            $parts[] = 'Проект: '.$project;
        }

        if (preg_match('/(?:бюджет|сумма)\s*[:№-]?\s*([\d\s]+(?:[,.]\d{1,2})?\s*(?:руб|₽)?)/iu', $text, $matches)) {
            $parts[] = 'Бюджет: '.trim($matches[1]);
        }

        return $parts ? implode('; ', $parts) : null;
    }

    private function phraseAfter(string $text, array $labels): ?string
    {
        $alternatives = implode('|', array_map(static fn ($label) => preg_quote($label, '/'), $labels));

        if (! preg_match('/(?:'.$alternatives.')\s*[:№-]?\s*([^,;]+?)(?=\s+(?:инн|кпп|огрн|адрес|контакт|тел|email|услуга|проект|бюджет|сумма|комментарий)\b|[,;]|$)/iu', $text, $matches)) {
            return null;
        }

        return trim(Arr::get($matches, 1, ''), " \t\n\r\0\x0B«»\"");
    }

    private function matchValue(string $text, string $pattern, int $group = 1): ?string
    {
        if (! preg_match($pattern, $text, $matches)) {
            return null;
        }

        return trim((string) Arr::get($matches, $group));
    }
}
