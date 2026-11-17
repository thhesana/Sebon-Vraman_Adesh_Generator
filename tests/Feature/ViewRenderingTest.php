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

    public function test_position_master_pages_render(): void
    {
        $position = new \App\Models\DesignationType(['designationType' => 'Director', 'designationTypeInNepali' => 'निर्देशक']);
        $position->id = 4;
        $position->exists = true;
        $position->employees_count = 3;

        $this->assertStringContainsString('href="'.route('positions.index').'"', view('layouts._nav')->render());

        $index = view('position.index', ['positions' => $this->page([$position]), 'search' => ''])->render();
        $this->assertStringContainsString('निर्देशक', $index);
        $this->assertStringContainsString(route('positions.edit', 4), $index);

        $this->assertStringContainsString('name="designationTypeInNepali"', view('position.create')->render());

        $edit = view('position.edit', compact('position'))->render();
        $this->assertStringContainsString('value="Director"', $edit);
        $this->assertStringContainsString(route('positions.update', 4), $edit);
    }

    public function test_fiscal_year_pages_show_and_pair_bs_dates(): void
    {
        $withBs = new FiscalYear(['fy' => '82/83', 'fy_startdate' => '2025-07-17', 'fy_enddate' => '2026-07-16',
            'fy_startdateBS' => '2082-04-01', 'fy_enddateBS' => '2083-03-31', 'fy_status' => 'ACTIVE']);
        $withBs->fiscal_year_master_id = 1;
        $withoutBs = new FiscalYear(['fy' => '83/84', 'fy_startdate' => '2026-07-17', 'fy_enddate' => '2027-07-16', 'fy_status' => 'ACTIVE']);
        $withoutBs->fiscal_year_master_id = 2;

        $index = view('fiscal_year.index', ['fiscalYears' => collect([$withBs, $withoutBs])])->render();
        $this->assertStringNotContainsString('Start Date (AD)', $index);
        $this->assertMatchesRegularExpression('/2082-04-01<\/span> <small class="text-muted">\(BS\)<\/small><br>\s*<small class="text-muted">2025-07-17 \(AD\)<\/small>/', $index);
        $this->assertSame(1, substr_count($index, 'data-fy-missing-bs'));
        $this->assertStringContainsString('js/fiscal_year/bs-dates.js', $index);
        $this->assertStringContainsString(json_encode(route('fiscal_years.bs_dates')), $index);

        $edit = view('fiscal_year.edit', ['row' => $withBs])->render();
        $this->assertStringContainsString('data-bs-pair="#fy_startdateBS"', $edit);
        $this->assertStringContainsString('value="2083-03-31"', $edit);
        $this->assertStringContainsString('calender/nepali.datepicker.v5.0.6.min.js', view('fiscal_year.create')->render());
    }

    private function oldIntlRecord(): \App\Models\OldInternationalTada
    {
        $record = new \App\Models\OldInternationalTada([
            'fiscal_year_master_id' => 3, 'Chalani_id' => 42, 'form_dateBS' => '2083-07-20',
            'EmpPersonalCode' => 'E001', 'Country_id' => 7, 'travel_objective' => 'IOSCO meeting',
            'travelDateStartBS' => '2083-07-24', 'travelDateEndBS' => '2083-07-29',
            'daily_rate_usd' => 225, 'total_days' => 5.5, 'total_usd' => 1237.5,
            'exchange_rate' => 151.47, 'exchange_rate_dateBS' => '2083-07-20', 'total_npr' => 187444.13,
            'DressAllowance' => false, 'dress_allowance_npr' => 0, 'Designation_id' => 4,
        ]);
        $record->Old_International_tada_id = 5;
        $record->exists = true;
        $fy = new FiscalYear(['fy' => '2083/84']);
        $record->setRelation('fiscalYear', $fy);
        $record->setRelation('employee', new Employee(['EmpName' => 'Ram Shrestha', 'EmpNameInNepali' => 'राम श्रेष्ठ']));
        $record->setRelation('country', new Country(['Country_name' => 'Japan']));
        $record->setRelation('city', null);
        $record->setRelation('verifier', null);
        $record->setRelation('designation', new \App\Models\DesignationType(['designationType' => 'Director', 'designationTypeInNepali' => 'निर्देशक']));

        return $record;
    }

    public function test_old_international_pages_render(): void
    {
        $record = $this->oldIntlRecord();

        $html = view('layouts._nav')->render();
        $this->assertMatchesRegularExpression("/USD Rate.*Old Int&#039;l Travel/s", $html);

        $index = view('old_international.index', ['records' => $this->page([$record]), 'search' => ''])->render();
        $this->assertStringContainsString('42/2083/84', $index);
        $this->assertStringContainsString('187,444.13', $index);
        $this->assertStringContainsString(route('old_international.print', 5), $index);

        $lookups = [
            'countries' => collect([7 => 'Japan']),
            'cities' => collect(), 'verifiers' => collect(), 'employees' => collect(),
            'designations' => collect([(object) ['id' => 4, 'designationType' => 'Director', 'designationTypeInNepali' => 'निर्देशक']]),
        ];
        $add = view('old_international.add', ['record' => new \App\Models\OldInternationalTada] + $lookups)->render();
        $this->assertStringContainsString('js/old_international/form.js', $add);
        foreach (['data-auto', 'btnRecalculate', 'name="remarks"', 'TadaDefinerMasterBylevel_id', 'name="DressAllowance"'] as $gone) {
            $this->assertStringNotContainsString($gone, $add);
        }

        $edit = view('old_international.edit', ['record' => $record] + $lookups)->render();
        $this->assertStringContainsString('value="151.47"', $edit);
        $this->assertStringContainsString('value="2083-07-20"', $edit);
        $this->assertStringContainsString('name="travelDateStartBS"', $edit);
        $this->assertMatchesRegularExpression('/<option value="4"\s+selected>\s*Director – निर्देशक/', $edit);
        $this->assertStringNotContainsString('fiscal_year_master_id', $edit);
        $this->assertStringNotContainsString('type="date"', $edit);

        $print = view('old_international.print', ['record' => $record])->render();
        foreach (['225', '5.50', '1,237.50', '151.47', '187,444.13', 'दुई वर्ष पूरा नभएको', 'राम श्रेष्ठ', 'संख्या: 42/2083/84',
            '(मिति 2083/07/20)', '2083/07/24 देखि', 'data-bs="2083-07-29"', '२. पद: निर्देशक'] as $text) {
            $this->assertStringContainsString($text, $print);
        }
    }

    public function test_standalone_pages_link_their_own_stylesheets(): void
    {
        $this->assertStringContainsString('css/usd/converter.css', view('usd.converter', [
            'rate' => null, 'dbMessage' => '', 'usd' => '', 'converted' => '', 'lastError' => 'boom',
        ])->render());
    }
}
