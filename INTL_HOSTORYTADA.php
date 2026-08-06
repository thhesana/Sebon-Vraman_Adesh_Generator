<?php
include 'db.php';
include 'header.php';   // <-- ADDED

// Get search keyword
$search = $_GET['search'] ?? "";

// Main query with SEARCH FILTER
$sql = "
SELECT 
    ei.EmpPersonalCode,
    ei.EmpName,
    it.Batch_id,
    cm.Country_name,
    it.travelDateStart,
    it.travelDateEnd,
    CASE 
        WHEN it.travelDateStart IS NOT NULL 
            THEN DATEDIFF(DAY, it.travelDateStart, GETDATE()) 
        ELSE NULL
    END AS DaysDifference
FROM Employee_Information ei
LEFT JOIN (
        SELECT 
            EmpPersonalCode,
            MAX(travelDateStart) AS LatestTravelStart
        FROM International_tada
        GROUP BY EmpPersonalCode
) latest ON ei.EmpPersonalCode = latest.EmpPersonalCode
LEFT JOIN International_tada it 
    ON ei.EmpPersonalCode = it.EmpPersonalCode
   AND it.travelDateStart = latest.LatestTravelStart
LEFT JOIN CountryMaster cm
    ON it.Country_id = cm.Country_id
WHERE 
    ei.EmpPersonalCode LIKE '%$search%' OR
    ei.EmpName LIKE '%$search%' OR
    cm.Country_name LIKE '%$search%' OR
    it.Batch_id LIKE '%$search%'
ORDER BY ei.EmpPersonalCode, it.travelDateStart DESC;

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
<title>International TADA Employee Status</title>

<style>
    body {
        font-family: Arial;
    }
    .search-box {
        margin-top: 20px;
        margin-bottom: 15px;
    }
    .search-input {
        padding: 8px;
        width: 250px;
        border: 1px solid gray;
        border-radius: 5px;
    }
    .search-btn {
        padding: 8px 15px;
        background: #003366;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
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

<h2><center>International Travel  Employee Status by Lastest Visit</center></h2>
<center>
<!-- SEARCH FILTER -->
<form method="GET" class="search-box">
    <input type="text" name="search" class="search-input" 
           placeholder="Search by Emp Code, Name, Country, Batch..."
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit" class="search-btn">Search</button>
</form></center>

<table>
    <tr>
        
        <th>Employee Name</th>
       
        <th>Country</th>
        <th>Travel Start</th>
        <th>Travel End</th>
        <th>Days Since Travel Start</th>
    </tr>

<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

    $country = $row['Country_name'] ?? "N/A";
    $batch = $row['Batch_id'] ?? "";
    $travelStart = $row['travelDateStart'] ? $row['travelDateStart']->format('Y-m-d') : "";
    $travelEnd = $row['travelDateEnd'] ? $row['travelDateEnd']->format('Y-m-d') : "";
    $daysDiff = $row['DaysDifference'];

    // Status color
    if ($daysDiff === null) {
        $status = "";
    } else {
        if ($daysDiff > 700) {
            $status = "<div class='green-box'>{$daysDiff} days</div>";
        } else {
            $status = "<div class='red-box'>{$daysDiff} days</div>";
        }
    }
?>
    <tr>
       
        <td><?php echo htmlspecialchars($row['EmpName']); ?></td>
       
        <td><?php echo htmlspecialchars($country); ?></td>
        <td><?php echo $travelStart; ?></td>
        <td><?php echo $travelEnd; ?></td>
        <td><?php echo $status; ?></td>
    </tr>
<?php } ?>

</table>

</body>
</html>
