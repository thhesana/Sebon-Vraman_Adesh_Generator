$(function () {
    const $country = $('#country_id');
    const $city = $('#city_name');

    // searchable country dropdown
    $country.select2({ placeholder: '-- Select Country --', allowClear: true });

    // the city field stays disabled until a country is chosen
    $country.on('change', function () {
        $city.prop('disabled', !$(this).val());
    });
});
