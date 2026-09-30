<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    private const PER_PAGE = 5;

    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = (string) $request->query('search', '');

        $query = Country::query();
        if ($search !== '' && $search !== '0') {
            $query->where('Country_name', 'like', '%'.$search.'%');
        }

        $totalRecords = (clone $query)->count();
        $totalPages = (int) ceil($totalRecords / self::PER_PAGE);

        $countries = $query
            ->select(['Country_id', 'Country_name', 'extra33percent_country'])
            ->orderBy('Country_name')
            ->offset(($page - 1) * self::PER_PAGE)
            ->limit(self::PER_PAGE)
            ->get();

        return view('country.index', compact('countries', 'page', 'search', 'totalPages'));
    }

    public function edit(Request $request)
    {
        $id = $request->query('id');
        if ($id === null || ! is_numeric($id)) {
            abort(400, 'Invalid Request');
        }

        $country = Country::findOrFail((int) $id);

        return view('country.edit', compact('country'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'Country_id' => ['required', 'integer'],
            'Country_name' => ['required', 'string', 'max:255'],
            'extra33percent_country' => ['required', 'in:0,1'],
        ]);

        Country::where('Country_id', $data['Country_id'])->update([
            'Country_name' => $data['Country_name'],
            'extra33percent_country' => (int) $data['extra33percent_country'],
        ]);

        return redirect('/countrylist.php');
    }
}
