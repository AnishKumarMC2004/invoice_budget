<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateService
{
    public function latest(): array
    {
        return Cache::remember('exchange-rates-usd', now()->addHours(6), function (): array {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get('https://api.frankfurter.app/latest', [
                    'from' => 'USD',
                    'to' => 'EUR,GBP,INR',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Currency rates could not be loaded from Frankfurter.');
            }

            $data = $response->json();
            $rates = is_array($data) ? ($data['rates'] ?? null) : null;
            if (
                ! is_array($data)
                || ! is_string($data['date'] ?? null)
                || strtotime($data['date']) === false
                || ! is_array($rates)
                || ! is_numeric($rates['EUR'] ?? null)
                || ! is_numeric($rates['GBP'] ?? null)
                || ! is_numeric($rates['INR'] ?? null)
            ) {
                throw new RuntimeException('Currency rates API returned an unexpected response.');
            }

            return $data;
        });
    }
}
