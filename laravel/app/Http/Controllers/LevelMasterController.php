<?php

namespace App\Http\Controllers;

use App\Models\TadaDefinerLevel;

class LevelMasterController extends Controller
{
    public function index()
    {
        $levels = TadaDefinerLevel::query()
            ->select(['TadaDefinerMasterBylevel_name', 'tadaInUSD', 'createddate'])
            ->orderByRaw("CASE WHEN TadaDefinerMasterBylevel_name = 'Chairman' THEN 0 ELSE 1 END")
            ->orderByDesc('tadaInUSD')
            ->get();

        return view('levels.index', compact('levels'));
    }
}
