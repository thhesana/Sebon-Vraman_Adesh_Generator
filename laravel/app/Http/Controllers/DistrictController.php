<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDistrictRequest;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $districts = District::query()
            ->search($search)
            ->orderByDesc('District_id')
            ->paginate(5)
            ->withQueryString();

        return view('district.index', compact('districts', 'search'));
    }

    public function create()
    {
        return view('district.create');
    }

    public function store(StoreDistrictRequest $request)
    {
        District::create([
            'District_name' => $request->validated('district_name'),
            'District_name_nepali' => $request->validated('district_name_nepali'),
        ]);

        return redirect()->route('districts.create')->with('success', 'District added successfully.');
    }
}
