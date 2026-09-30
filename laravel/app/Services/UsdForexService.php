<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the USD -> NPR rate from the NRB API and stores it with sp_UpsertUSDForex.
 */
class UsdForexService
{
    /** Last failure reason from fetchUsdRate(), shown on the converter page. */
    public ?string $lastError = null;

    /**
     * @return array{unit:mixed,buy:float,sell:float,date:string}|null
     */
    public function fetchUsdRate(): ?array
    {
        $today = date('Y-m-d');
        $from  = date('Y-m-d', strtotime('-7 days'));

        try {
            // The NRB server occasionally drops the TLS connection, so retry a few times.
            $response = Http::withoutVerifying()
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(3, 2000, throw: false)
                ->get('https://www.nrb.org.np/api/forex/v1/rates', [
                    'page' => 1, 'per_page' => 50, 'from' => $from, 'to' => $today,
                ]);
        } catch (Exception $e) {
            $this->lastError = 'Connection error: ' . $e->getMessage();
            Log::error('NRB API request failed: ' . $e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            $this->lastError = 'HTTP status ' . $response->status();
            Log::error('NRB API HTTP error: ' . $response->status());

            return null;
        }

        $payload = $response->json('data.payload');
        if (empty($payload)) {
            $this->lastError = 'No payload data found in API response';

            return null;
        }

        $latest = end($payload);
        foreach ($latest['rates'] ?? [] as $rate) {
            if (($rate['currency']['iso3'] ?? null) === 'USD') {
                return [
                    'unit' => $rate['currency']['unit'] ?? 1,
                    'buy'  => (float) $rate['buy'],
                    'sell' => (float) $rate['sell'],
                    'date' => $latest['date'] ?? $today,
                ];
            }
        }

        $this->lastError = 'USD rate not found in API response';

        return null;
    }

    /**
     * @return array{success:bool,action?:string,date?:string,amount?:mixed,oldAmount?:mixed,message?:string}
     */
    public function saveRate(string $date, float $amount): array
    {
        try {
            $rows = DB::select('EXEC sp_UpsertUSDForex ?, ?', [date('Y-m-d', strtotime($date)), $amount]);
        } catch (Exception $e) {
            Log::error('sp_UpsertUSDForex failed: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }

        $result = (array) ($rows[0] ?? []);

        return [
            'success'   => true,
            'action'    => $result['Action'] ?? 'COMPLETED',
            'date'      => $result['Date'] ?? $date,
            'amount'    => $result['Amount'] ?? $amount,
            'oldAmount' => $result['OldAmount'] ?? null,
        ];
    }
}
