<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\TadaDefinerLevel;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Renders the master-data views with in-memory models (no database) to prove the
 * layout, Blade components and extracted css/js references all compile and output correctly.
 */
class ViewRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // $errors is normally shared by the session middleware; views rendered directly need it.
        View::share('errors', new ViewErrorBag);
        $this->actingAs(new User(['username' => 'tester']));
    }

    private function page(iterable $items): LengthAwarePaginator
    {
        $items = collect($items);

        return new LengthAwarePaginator($items, $items->count(), 15, 1, ['path' => '/']);
    }

    public function test_layout_exposes_usd_url_for_the_international_tab_and_loads_assets(): void
    {
        $html = view('levels.index', ['levels' => collect()])->render();

        $this->assertStringContainsString('css/app.css', $html);
        $this->assertStringContainsString('js/app.js', $html);
        $this->assertStringContainsString('id="intlVramanTab"', $html);
        $this->assertStringContainsString('data-usd-url="'.route('usd.converter').'"', $html);
        $this->assertStringNotContainsString('<style', $html);
    }

    public function test_dashboard_passes_chart_data_through_data_attributes(): void
    {
        $html = view('dashboard', [
            'username' => 'tester', 'totalDom' => 1234, 'totalInt' => 5,
            'labels' => ['Jan 2026'], 'domData' => [3], 'intData' => [4],
        ])->render();

        $this->assertStringContainsString('1,234', $html);
        $this->assertStringContainsString('data-labels="[&quot;Jan 2026&quot;]"', $html);
        $this->assertStringContainsString('data-domestic="[3]"', $html);
        $this->assertStringContainsString('js/dashboard/index.js', $html);
    }

    public function test_levels_and_usd_rates_render(): void
    {
        $level = new TadaDefinerLevel(['TadaDefinerMasterBylevel_name' => 'Chairman', 'tadaInUSD' => 300]);
        $this->assertStringContainsString('Chairman', view('levels.index', ['levels' => collect([$level])])->render());

        $html = view('usd.rates', ['rates' => collect()])->render();
        $this->assertStringContainsString('No records found.', $html);
    }

    public function test_country_and_city_lists_render_with_search_and_pagination(): void
    {
        $country = new Country(['Country_name' => 'Japan', 'extra33percent_country' => 1]);
        $country->Country_id = 7;
        $html = view('country.index', ['countries' => $this->page([$country]), 'search' => 'jap'])->render();
        $this->assertStringContainsString('Japan', $html);
        $this->assertStringContainsString('value="jap"', $html);

        $empty = view('city.index', ['cities' => $this->page([]), 'search' => 'zzz'])->render();
        $this->assertStringContainsString('No cities found', $empty);
        $this->assertStringContainsString('Clear', $empty);
    }

    public function test_country_edit_selects_the_stored_value(): void
    {
        $country = new Country(['Country_name' => 'Japan', 'extra33percent_country' => 0]);
        $country->Country_id = 7;

        $html = view('country.edit', ['country' => $country])->render();

        $this->assertMatchesRegularExpression('/<option value="0"\s+selected>No<\/option>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="1"\s+selected>/', $html);
    }

    public function test_employee_forms_render_create_and_edit(): void
    {
        $designations = collect([(object) ['designationType' => 'Officer']]);
        $levels = collect([(object) ['levelName' => 'Level 7']]);

        $create = view('employee.create', compact('designations', 'levels'))->render();
        $this->assertStringContainsString('-- Select Designation --', $create);
        $this->assertStringContainsString('js/employee/form.js', $create);

        $emp = new Employee(['EmpName' => 'Sita', 'Designation' => 'Officer', 'LevelName' => 'Level 7', 'Gender' => 'Female', 'Email' => 's@x.np']);
        $emp->EmpPersonalCode = 'E001';
        $edit = view('employee.edit', compact('emp', 'designations', 'levels'))->render();
        $this->assertStringContainsString('value="E001"', $edit);
        $this->assertMatchesRegularExpression('/<option value="Officer"\s+selected>/', $edit);
        $this->assertMatchesRegularExpression('/<option value="Female"\s+selected>/', $edit);
    }

    public function test_city_district_and_fiscal_year_forms_render(): void
    {
        $country = new Country(['Country_name' => 'Japan']);
        $country->Country_id = 7;
        $countries = collect([$country]);

        $create = view('city.create', compact('countries'))->render();
        $this->assertStringContainsString('js/city/create.js', $create);
        $this->assertMatchesRegularExpression('/<input type="text" name="city_name"[^>]*disabled/', $create);

        $city = new City(['City_name' => 'Tokyo', 'Country_id' => 7]);
        $city->City_id = 1;
        $this->assertMatchesRegularExpression('/<option value="7"\s+selected>Japan<\/option>/', view('city.edit', compact('city', 'countries'))->render());

        $this->assertStringContainsString('lang="ne"', view('district.create')->render());
        $districts = $this->page([new District(['District_name' => 'Kathmandu', 'District_name_nepali' => 'x'])]);
        $this->assertStringContainsString('Kathmandu', view('district.index', ['districts' => $districts, 'search' => ''])->render());

        $this->assertStringContainsString('-- Select Status --', view('fiscal_year.create')->render());

        $row = new FiscalYear(['fy' => '2082/83', 'fy_startdate' => '2025-07-17', 'fy_enddate' => '2026-07-16', 'fy_status' => 'ACTIVE']);
        $row->fiscal_year_master_id = 1;
        $edit = view('fiscal_year.edit', compact('row'))->render();
        $this->assertStringContainsString('value="2025-07-17"', $edit);
        $this->assertMatchesRegularExpression('/<option value="ACTIVE"\s+selected>/', $edit);

        $index = view('fiscal_year.index', ['fiscalYears' => collect()])->render();
        $this->assertStringContainsString('No records found.', $index);
    }

    public function test_standalone_pages_link_their_own_stylesheets(): void
    {
        $this->assertStringContainsString('css/usd/converter.css', view('usd.converter', [
            'rate' => null, 'dbMessage' => '', 'usd' => '', 'converted' => '', 'lastError' => 'boom',
        ])->render());
    }
}
