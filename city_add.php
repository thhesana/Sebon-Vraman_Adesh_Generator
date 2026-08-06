<?php
include 'db.php';
include 'HEADER.php';

// Fetch countries for dropdown
$countrySql = "SELECT Country_id, Country_name FROM CountryMaster ORDER BY Country_name";
$countryStmt = sqlsrv_query($conn, $countrySql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city_name = $_POST['city_name'];
    $country_id = $_POST['country_id'];

    if (!$country_id) {
        die("<script>alert('Please select a country');window.history.back();</script>");
    }

    $insertSql = "
        INSERT INTO CityMaster (City_name, Country_id, createddate)
        VALUES (?, ?, GETDATE())
    ";

    $params = array($city_name, $country_id);

    if (sqlsrv_query($conn, $insertSql, $params)) {
        echo "<script>alert('City Added Successfully');window.location='cityList.php';</script>";
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<h2 class="text-center mt-4">Add New City</h2>
<div class="container mt-3">
    <form method="POST">

        <div class="mb-3">
            <label>Select Country:</label>
            <select name="country_id" id="country_id" class="form-control" required>
                <option value="">-- Select Country --</option>
                <?php while ($c = sqlsrv_fetch_array($countryStmt, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $c['Country_id']; ?>"><?= $c['Country_name']; ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label>City Name:</label>
            <input type="text" name="city_name" id="city_name" class="form-control" disabled required>
        </div>

        <button class="btn btn-primary">Save City</button>
        <a href="cityList.php" class="btn btn-secondary">Back</a>

    </form>
</div>

<!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        // apply Select2 on country select
        $('#country_id').select2({
            placeholder: "-- Select Country --",
            allowClear: true
        });

        // enable city field only after selecting a country
        $('#country_id').on('change', function () {
            if ($(this).val()) {
                $('#city_name').prop('disabled', false);
            } else {
                $('#city_name').prop('disabled', true);
            }
        });
    });
</script>
