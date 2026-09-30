<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * routes/legacy.php keeps the old *.php bookmarks working.
 */
class LegacyRedirectTest extends TestCase
{
    /** @return array<string,array{0:string,1:string}> */
    public static function simpleRedirects(): array
    {
        return [
            'index.php'                    => ['/index.php', 'login'],
            'dashboard.php'                => ['/dashboard.php', 'dashboard'],
            'ChangePassword.php'           => ['/ChangePassword.php', 'password.change'],
            'usd_rate.php'                 => ['/usd_rate.php', 'usd.rates'],
            'usdforexudater.php'           => ['/usdforexudater.php', 'usd.converter'],
            'orderlevel.php'               => ['/orderlevel.php', 'levels.index'],
            'countrylist.php'              => ['/countrylist.php', 'countries.index'],
            'cityLIst.php'                 => ['/cityLIst.php', 'cities.index'],
            'city_add.php'                 => ['/city_add.php', 'cities.create'],
            'DistrictList.php'             => ['/DistrictList.php', 'districts.index'],
            'add_district.php'             => ['/add_district.php', 'districts.create'],
            'employee_view.php'            => ['/employee_view.php', 'employees.index'],
            'employee_add.php'             => ['/employee_add.php', 'employees.create'],
            'fiscal_year.php'              => ['/fiscal_year.php', 'fiscal_years.index'],
            'add_fiscal_year.php'          => ['/add_fiscal_year.php', 'fiscal_years.create'],
            'DomesticTadaView.php'         => ['/DomesticTadaView.php', 'domestic.index'],
            'AddDomesticTada.php'          => ['/AddDomesticTada.php', 'domestic.create'],
            'DOMESTIC_TADAREPORT.php'      => ['/DOMESTIC_TADAREPORT.php', 'domestic.report'],
            'InternationalVraman.php'      => ['/InternationalVraman.php', 'international.index'],
            'add_international_tada.php'   => ['/add_international_tada.php', 'international.create'],
            'INTL_HOSTORYTADA.php'         => ['/INTL_HOSTORYTADA.php', 'international.history'],
            'INTERNATIONAL_TADAREPORT.php' => ['/INTERNATIONAL_TADAREPORT.php', 'international.report'],
        ];
    }

    #[DataProvider('simpleRedirects')]
    public function test_legacy_url_redirects_permanently_to_named_route(string $path, string $routeName): void
    {
        $this->get($path)
            ->assertStatus(301)
            ->assertRedirect(route($routeName));
    }

    public function test_legacy_url_is_matched_case_insensitively_via_lowercase_alias(): void
    {
        $this->get('/domestictadaview.php')->assertStatus(301)->assertRedirect(route('domestic.index'));
    }

    /** @return array<string,array{0:string,1:string,2:string,3:string,4:string}> */
    public static function parameterRedirects(): array
    {
        return [
            'edit_country.php'             => ['/edit_country.php', 'id', '5', 'countries.edit', 'country'],
            'city_edit.php'                => ['/city_edit.php', 'id', '7', 'cities.edit', 'city'],
            'employee_edit.php'            => ['/employee_edit.php', 'code', 'E001', 'employees.edit', 'employee'],
            'edit_fiscal_year.php'         => ['/edit_fiscal_year.php', 'id', '3', 'fiscal_years.edit', 'fiscal_year'],
            'EditDomesticTada.php'         => ['/EditDomesticTada.php', 'batch_id', 'DBATCH001', 'domestic.edit', 'batch'],
            'PrintDomesticTada.php'        => ['/PrintDomesticTada.php', 'batch_id', 'DBATCH001', 'domestic.print', 'batch'],
            'edit_international_tada.php'  => ['/edit_international_tada.php', 'batch_id', 'BATCH001', 'international.edit', 'batch'],
            'print_international_tada.php' => ['/print_international_tada.php', 'batch_id', 'BATCH001', 'international.print', 'batch'],
        ];
    }

    #[DataProvider('parameterRedirects')]
    public function test_legacy_url_with_query_parameter_redirects_to_record_route(string $path, string $query, string $value, string $routeName, string $routeParam): void
    {
        $this->get($path.'?'.$query.'='.$value)
            ->assertStatus(301)
            ->assertRedirect(route($routeName, [$routeParam => $value]));
    }

    #[DataProvider('parameterRedirects')]
    public function test_legacy_url_without_its_query_parameter_is_not_found(string $path): void
    {
        $this->get($path)->assertNotFound();
    }
}
