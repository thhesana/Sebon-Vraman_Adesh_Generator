<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection file
include 'db.php';
include 'header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fy = $_POST['fy'];
    $fy_startdate = $_POST['fy_startdate'];
    $fy_enddate = $_POST['fy_enddate'];
    $fy_status = $_POST['fy_status'];

    if (empty($fy) || empty($fy_startdate) || empty($fy_enddate) || empty($fy_status)) {
        echo "<script>alert('Please fill in all fields!');</script>";
    } else {
        $query = "
            INSERT INTO [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] 
            (fy, fy_startdate, fy_enddate, fy_status, created_date)
            VALUES (?, ?, ?, ?, GETDATE())";

        $params = array($fy, $fy_startdate, $fy_enddate, $fy_status);

        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt === false) {
            echo "Error adding fiscal year: " . print_r(sqlsrv_errors(), true);
        } else {
            echo "<script>alert('Fiscal Year added successfully!');</script>";
            echo "<script>window.location.href = 'fiscal_year.php';</script>";
        }
    }
}
?>

<!-- Bootstrap CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow rounded-4">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h4 class="mb-0">Add New Fiscal Year</h4>
                </div>
                <div class="card-body">
                    <form action="add_fiscal_year.php" method="POST">
                        <div class="mb-3">
                            <label for="fy" class="form-label">Fiscal Year</label>
                            <input type="text" name="fy" class="form-control" placeholder="Example: 2082/83" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_startdate" class="form-label">Start Date</label>
                            <input type="date" name="fy_startdate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_enddate" class="form-label">End Date</label>
                            <input type="date" name="fy_enddate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="fy_status" class="form-label">Status</label>
                            <select name="fy_status" class="form-select" required>
                                <option value="">-- Select Status --</option>
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Add Fiscal Year</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
