<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\LevelMasterController;
use Illuminate\Support\Facades\Route;

// Routes for the masters area (loaded inside the 'legacy.auth' group from routes/web.php)

// Level master
Route::match(['get', 'post'], '/orderlevel.php', [LevelMasterController::class, 'index'])->name('levels.index');

// Country
Route::match(['get', 'post'], '/countrylist.php', [CountryController::class, 'index'])->name('countries.index');
Route::match(['get', 'post'], '/countryList.php', [CountryController::class, 'index']);
Route::match(['get', 'post'], '/CountryList.php', [CountryController::class, 'index']);
Route::get('/edit_country.php', [CountryController::class, 'edit'])->name('countries.edit');
Route::post('/update_country.php', [CountryController::class, 'update'])->name('countries.update');

// City
Route::match(['get', 'post'], '/cityLIst.php', [CityController::class, 'index'])->name('cities.index');
Route::match(['get', 'post'], '/cityList.php', [CityController::class, 'index']);
Route::match(['get', 'post'], '/citylist.php', [CityController::class, 'index']);
Route::match(['get', 'post'], '/CityList.php', [CityController::class, 'index']);
Route::get('/city_add.php', [CityController::class, 'create'])->name('cities.create');
Route::post('/city_add.php', [CityController::class, 'store'])->name('cities.store');
Route::get('/city_edit.php', [CityController::class, 'edit'])->name('cities.edit');
Route::post('/city_edit.php', [CityController::class, 'update'])->name('cities.update');
Route::get('/get_cities.php', [CityController::class, 'byCountry'])->name('cities.by_country');

// District
Route::match(['get', 'post'], '/DistrictList.php', [DistrictController::class, 'index'])->name('districts.index');
Route::match(['get', 'post'], '/districtlist.php', [DistrictController::class, 'index']);
Route::match(['get', 'post'], '/districtList.php', [DistrictController::class, 'index']);
Route::match(['get', 'post'], '/district_list.php', [DistrictController::class, 'index']);
Route::get('/add_district.php', [DistrictController::class, 'create'])->name('districts.create');
Route::post('/add_district.php', [DistrictController::class, 'store'])->name('districts.store');

// Employee
Route::match(['get', 'post'], '/employee_view.php', [EmployeeController::class, 'index'])->name('employees.index');
Route::get('/employee_add.php', [EmployeeController::class, 'create'])->name('employees.create');
Route::post('/employee_add.php', [EmployeeController::class, 'store'])->name('employees.store');
Route::get('/employee_edit.php', [EmployeeController::class, 'edit'])->name('employees.edit');
Route::post('/employee_edit.php', [EmployeeController::class, 'update'])->name('employees.update');

// Fiscal year
Route::match(['get', 'post'], '/fiscal_year.php', [FiscalYearController::class, 'index'])->name('fiscal_years.index');
Route::get('/add_fiscal_year.php', [FiscalYearController::class, 'create'])->name('fiscal_years.create');
Route::post('/add_fiscal_year.php', [FiscalYearController::class, 'store'])->name('fiscal_years.store');
Route::get('/edit_fiscal_year.php', [FiscalYearController::class, 'edit'])->name('fiscal_years.edit');
Route::post('/edit_fiscal_year.php', [FiscalYearController::class, 'update'])->name('fiscal_years.update');
