<?php

namespace App\Http\Controllers;

use App\Models\TadaDefinerLevel;

class LevelMasterController extends Controller
{
    public function index()
    {
        $levels = TadaDefinerLevel::query()
            ->select(['TadaDefinerMasterBylevel_name', 'tadaInUSD', 'createddate'])
            ->chairmanFirst()
            ->get();

        return view('levels.index', compact('levels'));
    }
}
