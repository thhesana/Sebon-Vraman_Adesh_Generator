<?php

namespace App\Http\Controllers;

use App\Models\DomesticTada;
use App\Models\InternationalTada;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $since = Carbon::now()->subMonths(12);

        $totalDom = DomesticTada::where('domestic_travelDateStart', '>=', $since)->count();
        $totalInt = InternationalTada::where('travelDateStart', '>=', $since)->count();

        $domMap = $this->monthMap(DomesticTada::class, 'domestic_travelDateStart', $since);
        $intMap = $this->monthMap(InternationalTada::class, 'travelDateStart', $since);

        $labels = $domData = $intData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month    = Carbon::now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M Y');
            $domData[] = $domMap[$month->format('Y-m')] ?? 0;
            $intData[] = $intMap[$month->format('Y-m')] ?? 0;
        }

        return view('dashboard', [
            'username' => $request->session()->get('username', 'User'),
            'totalDom' => $totalDom,
            'totalInt' => $totalInt,
            'labels'   => $labels,
            'domData'  => $domData,
            'intData'  => $intData,
        ]);
    }

    /** @return array<string,int> keyed by 'YYYY-MM' */
    private function monthMap(string $model, string $column, Carbon $since): array
    {
        $rows = $model::selectRaw("YEAR($column) as y, MONTH($column) as m, COUNT(*) as cnt")
            ->where($column, '>=', $since)
            ->groupByRaw("YEAR($column), MONTH($column)")
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[sprintf('%04d-%02d', $row->y, $row->m)] = (int) $row->cnt;
        }

        return $map;
    }
}
