<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $search = (string) $request->query('search', '');

        $countries = Country::query()
            ->search($search)
            ->orderBy('Country_name')
            ->paginate(5)
            ->withQueryString();

        return view('country.index', compact('countries', 'search'));
    }

    public function edit(Country $country)
    {
        return view('country.edit', compact('country'));
    }

    public function update(UpdateCountryRequest $request, Country $country)
    {
        $country->update($request->validated());

        return redirect()->route('countries.index');
    }
}
