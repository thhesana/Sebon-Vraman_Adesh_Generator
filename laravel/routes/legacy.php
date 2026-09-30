<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy URL redirects
|--------------------------------------------------------------------------
|
| The pre-Laravel application was a set of *.php files. Bookmarks and links in
| SEBON MIS may still point at them, so each is redirected (301) to its new
| route. Nothing else in the application uses these URLs.
|
*/

$simple = [
    'index.php'                     => 'login',
    'dashboard.php'                 => 'dashboard',
    'ChangePassword.php'            => 'password.change',
    'usd_rate.php'                  => 'usd.rates',
    'usdforexudater.php'            => 'usd.converter',
    'orderlevel.php'                => 'levels.index',
    'countrylist.php'               => 'countries.index',
    'cityLIst.php'                  => 'cities.index',
    'city_add.php'                  => 'cities.create',
    'DistrictList.php'              => 'districts.index',
    'add_district.php'              => 'districts.create',
    'employee_view.php'             => 'employees.index',
    'employee_add.php'              => 'employees.create',
    'fiscal_year.php'               => 'fiscal_years.index',
    'add_fiscal_year.php'           => 'fiscal_years.create',
    'DomesticTadaView.php'          => 'domestic.index',
    'AddDomesticTada.php'           => 'domestic.create',
    'DOMESTIC_TADAREPORT.php'       => 'domestic.report',
    'InternationalVraman.php'       => 'international.index',
    'add_international_tada.php'    => 'international.create',
    'INTL_HOSTORYTADA.php'          => 'international.history',
    'INTERNATIONAL_TADAREPORT.php'  => 'international.report',
];

foreach ($simple as $file => $name) {
    Route::get($file, fn () => redirect()->route($name, [], 301));
    Route::get(strtolower($file), fn () => redirect()->route($name, [], 301));
}

// Legacy pages that took the record in the query string.
$withParam = [
    'edit_country.php'              => ['countries.edit', 'country', 'id'],
    'city_edit.php'                 => ['cities.edit', 'city', 'id'],
    'employee_edit.php'             => ['employees.edit', 'employee', 'code'],
    'edit_fiscal_year.php'          => ['fiscal_years.edit', 'fiscal_year', 'id'],
    'EditDomesticTada.php'          => ['domestic.edit', 'batch', 'batch_id'],
    'PrintDomesticTada.php'         => ['domestic.print', 'batch', 'batch_id'],
    'edit_international_tada.php'   => ['international.edit', 'batch', 'batch_id'],
    'print_international_tada.php'  => ['international.print', 'batch', 'batch_id'],
];

foreach ($withParam as $file => [$name, $param, $query]) {
    Route::get($file, function (Request $request) use ($name, $param, $query) {
        abort_unless($request->filled($query), 404);

        return redirect()->route($name, [$param => $request->query($query)], 301);
    });
}
