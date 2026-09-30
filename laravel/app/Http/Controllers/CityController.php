<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    private const PER_PAGE = 5;

    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $hasSearch = $search !== '' && $search !== '0';
        $offset = ($page - 1) * self::PER_PAGE;

        $query = DB::table('CityMaster as c')
            ->leftJoin('CountryMaster as co', 'c.Country_id', '=', 'co.Country_id');

        if ($hasSearch) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('c.City_name', 'like', $like)
                    ->orWhere('co.Country_name', 'like', $like);
            });
        }

        $totalRows = (clone $query)->count();
        $totalPages = (int) ceil($totalRows / self::PER_PAGE);

        $cities = $query
            ->select(['c.City_id', 'c.City_name', 'c.Country_id', 'co.Country_name'])
            ->orderBy('c.Country_id')
            ->orderBy('c.City_id')
            ->offset($offset)
            ->limit(self::PER_PAGE)
            ->get();

        return view('city.index', compact('cities', 'page', 'search', 'hasSearch', 'totalRows', 'totalPages', 'offset'));
    }

    public function create()
    {
        $countries = Country::orderBy('Country_name')->get(['Country_id', 'Country_name']);

        return view('city.create', compact('countries'));
    }

    public function store(Request $request)
    {
        if (! $request->filled('country_id')) {
            return back()->withInput()->with('alert', 'Please select a country');
        }

        $data = $request->validate([
            'city_name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer'],
        ]);

        City::create([
            'City_name' => $data['city_name'],
            'Country_id' => $data['country_id'],
            'createddate' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect('/cityLIst.php')->with('alert', 'City Added Successfully');
    }

    public function edit(Request $request)
    {
        $city = City::findOrFail($this->cityId($request));
        $countries = Country::orderBy('Country_name')->get(['Country_id', 'Country_name']);

        return view('city.edit', compact('city', 'countries'));
    }

    public function update(Request $request)
    {
        $city = City::findOrFail($this->cityId($request));

        $data = $request->validate([
            'city_name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer'],
        ]);

        City::where('City_id', $city->City_id)->update([
            'City_name' => $data['city_name'],
            'Country_id' => $data['country_id'],
        ]);

        return redirect('/cityLIst.php')->with('alert', 'City Updated Successfully');
    }

    /** JSON endpoint used by the international add/edit pages. */
    public function byCountry(Request $request)
    {
        $countryId = (int) $request->query('country_id', 0);

        if ($countryId <= 0) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Invalid country ID',
            ]);
        }

        try {
            $cities = City::where('Country_id', $countryId)
                ->orderBy('City_name')
                ->get(['City_id', 'City_name'])
                ->map(fn ($c) => ['City_id' => $c->City_id, 'City_name' => $c->City_name])
                ->values();

            return response()->json([
                'success' => true,
                'data' => $cities,
                'count' => $cities->count(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'data' => [],
                'error' => 'Server error',
                'message' => 'Could not fetch cities',
                'country_id' => $countryId,
            ], 500);
        }
    }

    private function cityId(Request $request)
    {
        $id = $request->query('id');
        if (! $id) {
            abort(400, 'City ID missing');
        }

        return $id;
    }
}
