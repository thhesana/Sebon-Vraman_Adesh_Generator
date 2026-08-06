<?php
include 'db.php';

$country_id = $_POST['Country_id'];
$name = $_POST['Country_name'];
$extra = $_POST['extra33percent_country'];

$sql = "UPDATE CountryMaster 
        SET Country_name = ?, extra33percent_country = ?
        WHERE Country_id = ?";

$params = [$name, $extra, $country_id];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

header("Location: countrylist.php");
exit;
?>
