<?php
include 'HEADER.php';
include 'db.php';

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='alert alert-danger mt-4'>Invalid Request</div>");
}

$country_id = intval($_GET['id']);

// Fetch country details
$sql = "SELECT Country_id, Country_name, extra33percent_country 
        FROM CountryMaster WHERE Country_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$country_id]);

if ($stmt === false) {
    die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}

$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Edit Country</h4>
        </div>

        <div class="card-body">

            <form action="update_country.php" method="POST">
                <input type="hidden" name="Country_id" value="<?= $data['Country_id'] ?>">

                <!-- Country Name -->
                <div class="mb-3">
                    <label class="form-label">Country Name</label>
                    <input type="text" 
                           name="Country_name" 
                           class="form-control" 
                           value="<?= htmlspecialchars($data['Country_name']) ?>" 
                           required>
                </div>

                <!-- Extra 33% -->
                <div class="mb-3">
                    <label class="form-label">Extra 33%?</label>
                    <select name="extra33percent_country" class="form-control">
                        <option value="1" <?= ($data['extra33percent_country'] == 1 ? 'selected' : '') ?>>Yes</option>
                        <option value="0" <?= ($data['extra33percent_country'] == 0 ? 'selected' : '') ?>>No</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="countrylist.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include 'FOOTER.php'; ?>
