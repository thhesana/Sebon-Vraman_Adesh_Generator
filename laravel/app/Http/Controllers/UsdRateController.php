<?php

namespace App\Http\Controllers;

use App\Models\UsdForex;
use App\Services\UsdForexService;
use Illuminate\Http\Request;

class UsdRateController extends Controller
{
    /** usd_rate.php — latest 12 stored rates. */
    public function index()
    {
        $rates = UsdForex::orderByDesc('conversion_date')->limit(12)->get();

        return view('usd.rates', compact('rates'));
    }

    /**
     * usdforexudater.php — fetches today's NRB rate, stores it, and offers a USD -> NPR converter.
     * Also called by the header tab via fetch() to refresh the rate before opening the international module.
     */
    public function converter(Request $request, UsdForexService $forex)
    {
        $rate = $forex->fetchUsdRate();
        $dbMessage = '';

        if ($rate) {
            $result = $forex->saveRate($rate['date'], $rate['sell']);

            if ($result['success']) {
                $dbMessage = match ($result['action']) {
                    'INSERTED'  => '✅ New rate inserted into database for ' . $result['date'],
                    'UPDATED'   => '🔄 Rate updated in database (Old: '
                        . (isset($result['oldAmount']) ? number_format((float) $result['oldAmount'], 4) : 'N/A')
                        . ' → New: ' . number_format((float) $result['amount'], 4) . ')',
                    'NO_CHANGE' => 'ℹ️ Rate already up-to-date in database',
                    default     => '✅ Rate saved to database successfully',
                };
            } else {
                $dbMessage = '❌ Database Error: ' . $result['message'];
            }
        }

        $usd = '';
        $converted = '';
        if ($rate && is_numeric($request->input('usd_amount'))) {
            $usd = (float) $request->input('usd_amount');
            $converted = $usd * $rate['sell'];
        }

        return view('usd.converter', [
            'rate'      => $rate,
            'dbMessage' => $dbMessage,
            'usd'       => $usd,
            'converted' => $converted,
            'lastError' => $forex->lastError,
        ]);
    }
}
