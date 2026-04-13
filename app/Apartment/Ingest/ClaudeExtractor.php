<?php

namespace App\Apartment\Ingest;

use Laravel\Ai\AnonymousAgent;

class ClaudeExtractor
{
    public const CATEGORIES = ['utility_invoice', 'contract', 'appliance_manual', 'building_notice', 'tax', 'insurance', 'other'];

    public const UTILITY_TYPES = ['electricity', 'water', 'internet'];

    /**
     * Extract structured fields from raw (Hungarian) document text.
     *
     * @return array<string, mixed>
     */
    public function extract(string $rawText, string $originalFilename): array
    {
        $instructions = $this->systemPrompt();
        $userPrompt = "Filename: {$originalFilename}\n\n--- DOCUMENT TEXT ---\n".$this->trimForPrompt($rawText);

        $agent = new AnonymousAgent($instructions, [], []);

        $response = $agent->prompt($userPrompt, model: config('apartment.claude_model'));
        $text = trim($response->text ?? '');

        $parsed = $this->parseJson($text);
        if ($parsed !== null) {
            return $this->normalize($parsed);
        }

        $retry = $agent->prompt(
            "Your previous reply was not valid JSON. Reply with ONLY the JSON object, nothing else.\n\n".$userPrompt,
            model: config('apartment.claude_model'),
        );
        $parsed = $this->parseJson(trim($retry->text ?? ''));

        if ($parsed === null) {
            throw new \RuntimeException('Claude did not return parseable JSON after retry');
        }

        return $this->normalize($parsed);
    }

    private function systemPrompt(): string
    {
        $cats = implode(', ', self::CATEGORIES);
        $utils = implode(', ', self::UTILITY_TYPES);

        return <<<PROMPT
You analyse Hungarian apartment-related documents (utility bills, contracts, appliance manuals, building notices, tax letters, insurance) and return strict JSON.

Reply with a single JSON object — no markdown, no commentary, no code fences. Use null for unknown fields. Dates as ISO YYYY-MM-DD. Numbers as numbers (no thousand separators, dot decimal).

Schema:
{
  "category": one of [{$cats}],
  "title_en": short English title (max 80 chars),
  "summary_en": 2-3 sentence English summary,
  "issued_on": ISO date or null,
  "period_start": ISO date or null,
  "period_end": ISO date or null,
  "counterparty": company / sender name or null (e.g. "ELMŰ", "UNIQA"),
  "amount_huf": number or null,
  "currency": "HUF" / "EUR" / etc or null,
  "utility": null OR {
    "utility_type": one of [{$utils}],
    "consumption_value": number or null,
    "consumption_unit": "kWh" / "m³" / "GJ" / etc or null,
    "meter_serial": string or null
  }
}

Only populate "utility" when category is "utility_invoice". Otherwise it must be null.
PROMPT;
    }

    private function trimForPrompt(string $text): string
    {
        $max = 24000;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max).' …[truncated]';
    }

    private function parseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $text, $m)) {
            $text = $m[1];
        }

        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first === false || $last === false || $last <= $first) {
            return null;
        }

        $json = substr($text, $first, $last - $first + 1);
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    private function normalize(array $data): array
    {
        $category = $data['category'] ?? 'other';
        if (! in_array($category, self::CATEGORIES, true)) {
            $category = 'other';
        }

        $utility = $data['utility'] ?? null;
        if ($category !== 'utility_invoice') {
            $utility = null;
        } elseif (is_array($utility)) {
            $type = $utility['utility_type'] ?? null;
            if (! in_array($type, self::UTILITY_TYPES, true)) {
                $utility['utility_type'] = null;
            }
        }

        return [
            'category' => $category,
            'title_en' => $this->stringOrNull($data['title_en'] ?? null),
            'summary_en' => $this->stringOrNull($data['summary_en'] ?? null),
            'issued_on' => $this->dateOrNull($data['issued_on'] ?? null),
            'period_start' => $this->dateOrNull($data['period_start'] ?? null),
            'period_end' => $this->dateOrNull($data['period_end'] ?? null),
            'counterparty' => $this->stringOrNull($data['counterparty'] ?? null),
            'amount_huf' => $this->numberOrNull($data['amount_huf'] ?? null),
            'currency' => $this->stringOrNull($data['currency'] ?? null),
            'utility' => $utility,
        ];
    }

    private function stringOrNull(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_string($v) ? trim($v) : (string) $v;
    }

    private function dateOrNull(mixed $v): ?string
    {
        if (! is_string($v) || $v === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function numberOrNull(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? (float) $v : null;
    }
}
