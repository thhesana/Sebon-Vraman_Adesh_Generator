<?php
// Start output buffering
ob_start();
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection file
include 'db.php'; // Adjust the path as needed
include 'header.php';
// Get the fiscal year ID from the URL query string
if (isset($_GET['id'])) {
    $fiscal_year_master_id = $_GET['id'];

    // Fetch the fiscal year data to edit
    $query = "SELECT fiscal_year_master_id, fy, fy_startdate, fy_enddate, fy_status FROM [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] WHERE fiscal_year_master_id = ?";
    $params = array($fiscal_year_master_id);
    $result = sqlsrv_query($conn, $query, $params);

    // Check for any errors
    if ($result === false) {
        die("Error fetching fiscal year data: " . print_r(sqlsrv_errors(), true));
    }

    // Check if the fiscal year record exists
    if (sqlsrv_has_rows($result)) {
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    } else {
        die("Fiscal year record not found.");
    }
} else {
    die("Invalid request. No fiscal year ID provided.");
}

// Check if the form is submitted to update the fiscal year
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fy = $_POST['fy'];
    $fy_startdate = $_POST['fy_startdate'];
    $fy_enddate = $_POST['fy_enddate'];
    $fy_status = $_POST['fy_status'];

    // Update fiscal year data in the database
    $update_query = "UPDATE [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] 
                     SET fy = ?, fy_startdate = ?, fy_enddate = ?, fy_status = ? 
                     WHERE fiscal_year_master_id = ?";
    $params = array($fy, $fy_startdate, $fy_enddate, $fy_status, $fiscal_year_master_id);
    $update_result = sqlsrv_query($conn, $update_query, $params);

    // Check for errors in the update query
    if ($update_result === false) {
        die("Error updating fiscal year data: " . print_r(sqlsrv_errors(), true));
    } else {
        // Redirect to the fiscal year master page after successful update
        header('Location: fiscal_year.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fiscal Year</title>
    <!-- Bootstrap CDN for Styling -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styling for Centered and Small Form -->
    <style>
        /* Make sure the form is centered on the page */
        .container {
            height: 100vh; /* Full viewport height */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 100%;
            max-width: 400px; /* Limit width of the form */
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-control {
            border-radius: 4px;
            box-shadow: none;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .btn-block {
            width: 100%;
        }
    </style>
</head>
<body>

<!-- Center the form in the middle of the screen with a smaller size -->
<div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="card">
        <h2 class="text-center">Edit Fiscal Year</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="fy">Fiscal Year:</label>
                <input type="text" class="form-control" name="fy" value="<?php echo htmlspecialchars($row['fy']); ?>" required>
            </div>
            <div class="form-group">
                <label for="fy_startdate">Start Date:</label>
                <input type="date" class="form-control" name="fy_startdate" value="<?php echo $row['fy_startdate']->format('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="fy_enddate">End Date:</label>
                <input type="date" class="form-control" name="fy_enddate" value="<?php echo $row['fy_enddate']->format('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="fy_status">Status:</label>
                <select class="form-control" name="fy_status" required>
                    <option value="ACTIVE" <?php echo ($row['fy_status'] == 'ACTIVE') ? 'selected' : ''; ?>>ACTIVE</option>
                    <option value="INACTIVE" <?php echo ($row['fy_status'] == 'INACTIVE') ? 'selected' : ''; ?>>INACTIVE</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Update Fiscal Year</button>
        </form>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
