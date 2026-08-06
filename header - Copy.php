<?php
include 'db.php';

// Get today's date (AD)
$today = date('Y-m-d');

// Main query
$sql = "
SELECT 
    it.Batch_id,
    it.EmpPersonalCode,
    ei.EmpName,
    it.Country_id,
    cm.Country_name,
    it.travelDateStart,
    it.travelDateEnd,
    DATEDIFF(DAY, it.travelDateStart, GETDATE()) AS DaysDifference
FROM International_tada it
LEFT JOIN Employee_Information ei 
    ON it.EmpPersonalCode = ei.EmpPersonalCode
LEFT JOIN CountryMaster cm
    ON it.Country_id = cm.Country_id
ORDER BY it.EmpPersonalCode, it.travelDateStart DESC;
";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>International TADA Status</title>

<style>
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 20px;
        font-family: Arial;
    }
    th, td {
        border: 1px solid #444;
        padding: 8px;
        text-align: left;
    }
    th {
        background: #003366;
        color: white;
    }
    .green-box {
        background: #c5f7c5;
        color: green;
        font-weight: bold;
        padding: 5px;
        border-radius: 5px;
        text-align: center;
    }
    .red-box {
        background: #ffc5c5;
        color: red;
        font-weight: bold;
        padding: 5px;
        border-radius: 5px;
        text-align: center;
    }
</style>

</head>
<body>

<h2>International TADA Employee Status</h2>

<table>
    <tr>
        <th>Batch ID</th>
        <th>Emp Code</th>
        <th>Employee Name</th>
        <th>Country</th>
        <th>Travel Start</th>
        <th>Travel End</th>
        <th>Days Since Travel Start</th>
        <th>Status</th>
    </tr>

<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

    $daysDiff = (int)$row['DaysDifference'];

    // Color status logic
    if ($daysDiff > 700) {
        $status = "<div class='green-box'>{$daysDiff} days</div>";
    } else {
        $status = "<div class='red-box'>{$daysDiff} days</div>";
    }
?>
    <tr>
        <td><?php echo $row['Batch_id']; ?></td>
        <td><?php echo $row['EmpPersonalCode']; ?></td>
        <td><?php echo htmlspecialchars($row['EmpName']); ?></td>
        <td><?php echo htmlspecialchars($row['Country_name']); ?></td>
        <td><?php echo $row['travelDateStart']->format('Y-m-d'); ?></td>
        <td><?php echo $row['travelDateEnd']->format('Y-m-d'); ?></td>
        <td><?php echo $daysDiff; ?></td>
        <td><?php echo $status; ?></td>
    </tr>
<?php } ?>

</table>

</body>
</html>
