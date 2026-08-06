<?php
include 'db.php'; // <-- your SQL Server connection file
include 'header.php';

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// SQL query
$sql = "
    SELECT TOP (12) 
        USDforexId,
        conversion_date,
        CASE 
            WHEN amount = FLOOR(amount) THEN FORMAT(amount, 'N0') 
            ELSE FORMAT(amount, 'N2')
        END AS amount,
        UpdatedDateBySebon
    FROM USDforex
    ORDER BY conversion_date DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("SQL error: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>USD Forex Rate (Last 12 Records)</title>
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 40px 20px;
    }

    h2 {
        text-align: center;
        color: #2d3748;
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 25px;
    }

    table {
        width: 70%;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        border-collapse: collapse;
    }

    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        border: none;
    }

    td {
        padding: 12px 15px;
        text-align: center;
        color: #2d3748;
        font-size: 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    tr:nth-child(even) {
        background: #f8f9fa;
    }

    tr:hover {
        background: #f0f4ff;
    }

    @media (max-width: 768px) {
        table {
            width: 95%;
        }
        
        th, td {
            padding: 10px;
            font-size: 12px;
        }

        h2 {
            font-size: 22px;
        }
    }
</style>
</head>

<body>

<h2>USD Forex Conversion Table (Latest 12 Records)</h2>

<table>
    <tr>
        <th>SN</th>
        <th>Conversion Date</th>
        <th>USD Amount</th>
        <th>Updated Date At SEBON</th>
    </tr>

    <?php 
    $sn = 1; // Auto-increment SN
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= $sn++; ?></td>

            <td>
                <?php 
                    if ($row['conversion_date'] instanceof DateTime) {
                        echo $row['conversion_date']->format('Y-M-d');
                    }
                ?>
            </td>

            <td><?= $row['amount']; ?></td>

            <td>
                <?php 
                    if ($row['UpdatedDateBySebon'] instanceof DateTime) {
                        echo $row['UpdatedDateBySebon']->format('Y-M-d H:i:s');
                    }
                ?>
            </td>
        </tr>
    <?php } ?>

</table>

</body>
</html>
