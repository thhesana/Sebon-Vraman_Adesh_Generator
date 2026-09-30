<?php

namespace App\Http\Controllers;

use App\Models\DomesticTada;
use App\Models\InternationalTada;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $since = Carbon::now()->subMonths(12);

        $domDates = DomesticTada::where('domestic_travelDateStart', '>=', $since)->pluck('domestic_travelDateStart');
        $intDates = InternationalTada::where('travelDateStart', '>=', $since)->pluck('travelDateStart');

        $domMap = $this->countByMonth($domDates);
        $intMap = $this->countByMonth($intDates);

        $labels = $domData = $intData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month     = Carbon::now()->startOfMonth()->subMonths($i);
            $labels[]  = $month->format('M Y');
            $domData[] = $domMap[$month->format('Y-m')] ?? 0;
            $intData[] = $intMap[$month->format('Y-m')] ?? 0;
        }

        return view('dashboard', [
            'username' => $request->user()->username,
            'totalDom' => $domDates->count(),
            'totalInt' => $intDates->count(),
            'labels'   => $labels,
            'domData'  => $domData,
            'intData'  => $intData,
        ]);
    }

    /**
     * @param  Collection<int,mixed>  $dates
     * @return array<string,int> record count keyed by 'YYYY-MM'
     */
    private function countByMonth(Collection $dates): array
    {
        return $dates
            ->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->all();
    }
}
