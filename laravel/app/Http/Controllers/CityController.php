<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $cities = City::query()
            ->with('country')
            ->search($search)
            ->orderBy('Country_id')
            ->orderBy('City_id')
            ->paginate(5)
            ->withQueryString();

        return view('city.index', compact('cities', 'search'));
    }

    public function create()
    {
        return view('city.create', ['countries' => Country::orderBy('Country_name')->get()]);
    }

    public function store(StoreCityRequest $request)
    {
        City::create([
            'City_name' => $request->validated('city_name'),
            'Country_id' => $request->validated('country_id'),
            'createddate' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('cities.index')->with('success', 'City Added Successfully');
    }

    public function edit(City $city)
    {
        return view('city.edit', [
            'city' => $city,
            'countries' => Country::orderBy('Country_name')->get(),
        ]);
    }

    public function update(UpdateCityRequest $request, City $city)
    {
        $city->update([
            'City_name' => $request->validated('city_name'),
            'Country_id' => $request->validated('country_id'),
        ]);

        return redirect()->route('cities.index')->with('success', 'City Updated Successfully');
    }

    /** JSON endpoint used by the international add/edit pages. */
    public function byCountry(Country $country)
    {
        $cities = $country->cities()
            ->orderBy('City_name')
            ->get(['City_id', 'City_name'])
            ->map(fn (City $c) => ['City_id' => $c->City_id, 'City_name' => $c->City_name])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $cities,
            'count' => $cities->count(),
        ]);
    }
}
