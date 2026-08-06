<?php
include 'db.php';
include 'HEADER.php';

$city_id = $_GET['id'] ?? null;
if (!$city_id) die("City ID missing");

// Fetch city info
$citySql = "SELECT * FROM CityMaster WHERE City_id = ?";
$cityStmt = sqlsrv_query($conn, $citySql, array($city_id));
$city = sqlsrv_fetch_array($cityStmt, SQLSRV_FETCH_ASSOC);

// Fetch countries for dropdown
$countrySql = "SELECT Country_id, Country_name FROM CountryMaster ORDER BY Country_name";
$countryStmt = sqlsrv_query($conn, $countrySql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city_name = $_POST['city_name'];
    $country_id = $_POST['country_id'];

    $updateSql = "
        UPDATE CityMaster
        SET City_name = ?, Country_id = ?
        WHERE City_id = ?
    ";

    $params = array($city_name, $country_id, $city_id);

    if (sqlsrv_query($conn, $updateSql, $params)) {
        echo "<script>alert('City Updated Successfully');window.location='citylist.php';</script>";
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>

<h2 class="text-center mt-4">Edit City</h2>
<div class="container mt-3">
    <form method="POST">

        <div class="mb-3">
            <label>City Name:</label>
            <input type="text" name="city_name" class="form-control" required 
                value="<?= $city['City_name']; ?>">
        </div>

        <div class="mb-3">
            <label>Select Country:</label>
            <select name="country_id" required class="form-control">
                <?php while ($c = sqlsrv_fetch_array($countryStmt, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $c['Country_id']; ?>"
                        <?= ($c['Country_id'] == $city['Country_id']) ? "selected" : ""; ?>>
                        <?= $c['Country_name']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <button class="btn btn-primary">Update City</button>
        <a href="cityList.php" class="btn btn-secondary">Back</a>

    </form>
</div>
