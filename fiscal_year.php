<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection file
include 'db.php'; // Adjust the path as needed
include 'header.php';

// Fetch fiscal year data
$query = "SELECT  fiscal_year_master_id, fy, fy_startdate, fy_enddate, fy_status, created_date FROM  [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] order by fiscal_year_master_id desc";
$result = sqlsrv_query($conn, $query); // Use sqlsrv_query for SQL Server

// Check for any errors
if ($result === false) {
    die("Error fetching fiscal year data: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiscal Year Master</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Century Gothic', sans-serif; 
        }

        .container {
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #000000;
        }

        .button-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .add-button {
            background-color: #28a745;
            color: white;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .add-button:hover {
            background-color: #218838;
        }

        /* Edit button styling */
        .edit-button {
            background-color: #007bff;
            color: white;
            font-size: 16px;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .edit-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .edit-button:active {
            background-color: #004085;
        }

        table {
            width: 100%;
            margin: 0 auto;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        td a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        td a:hover {
            color: #0056b3;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }

        .no-records {
            text-align: center;
            font-size: 18px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>FISCAL YEAR MASTER</h2>

    <div class="button-container">
        <button class="add-button" onclick="window.location.href='add_fiscal_year.php'">Add New Fiscal Year</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>SN</th>
                <th>Fiscal Year</th>
                <th>Start Date</th>
                <th>End Date</th>
               
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (sqlsrv_has_rows($result)) { // Check if there are any rows
                $sn = 1; // Initialize the serial number counter
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) { // Fetch rows as associative array
                    echo "<tr>";
                    echo "<td>" . $sn++ . "</td>"; // Display serial number and increment it
                    echo "<td>" . htmlspecialchars($row['fy']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['fy_startdate']->format('Y-m-d')) . "</td>"; // Format date as needed
                    echo "<td>" . htmlspecialchars($row['fy_enddate']->format('Y-m-d')) . "</td>"; // Format date as needed
                  
                    echo "<td>
                            <a href='edit_fiscal_year.php?id=" . $row['fiscal_year_master_id'] . "' class='edit-button'>Edit</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='no-records'>No records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
