<?php
include 'db.php';
include 'HEADER.php';

$code = $_GET['code'] ?? null;
if (!$code) die("Invalid employee code");

// Fetch employee
$empSql = "SELECT * FROM Employee_Information WHERE EmpPersonalCode = ?";
$empStmt = sqlsrv_query($conn, $empSql, [$code]);
$emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC);

// Dropdowns
$designationSql = "SELECT id, designationType FROM DesignationTypeMaster ORDER BY designationType";
$designationStmt = sqlsrv_query($conn, $designationSql);

$levelSql = "SELECT id, levelName FROM LevelNameMaster ORDER BY levelName";
$levelStmt = sqlsrv_query($conn, $levelSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nep = $_POST['EmpNameInNepali'];
    $eng = $_POST['EmpName'];
    $desig = $_POST['Designation'];
    $level = $_POST['LevelName'];
    $gender = $_POST['Gender'];
    $email = $_POST['Email'];

    $updateSql = "
        UPDATE Employee_Information SET
        EmpNameInNepali = ?, EmpName = ?, Designation = ?, LevelName = ?, Gender = ?, Email = ?
        WHERE EmpPersonalCode = ?
    ";

    $params = [$nep, $eng, $desig, $level, $gender, $email, $code];

    if (sqlsrv_query($conn, $updateSql, $params)) {
        echo "<script>alert('Employee Updated Successfully');window.location='employee_view.php';</script>";
    } else {
        print_r(sqlsrv_errors());
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<h2 class="text-center mt-4">Edit Employee</h2>
<div class="container mt-3">
<form method="POST">

    <div class="mb-3">
        <label>Employee Code:</label>
        <input type="text" value="<?= $emp['EmpPersonalCode']; ?>" class="form-control" readonly>
    </div>

    <div class="mb-3">
        <label>Name in Nepali:</label>
        <input type="text" name="EmpNameInNepali" value="<?= $emp['EmpNameInNepali']; ?>" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Name in English:</label>
        <input type="text" name="EmpName" value="<?= $emp['EmpName']; ?>" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Designation:</label>
        <select name="Designation" id="designation" class="form-control" required>
            <?php while($d = sqlsrv_fetch_array($designationStmt, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $d['designationType']; ?>" 
                    <?= ($d['designationType'] == $emp['Designation']) ? "selected" : ""; ?>>
                    <?= $d['designationType']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Level:</label>
        <select name="LevelName" id="level" class="form-control" required>
            <?php while($l = sqlsrv_fetch_array($levelStmt, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $l['levelName']; ?>" 
                    <?= ($l['levelName'] == $emp['LevelName']) ? "selected" : ""; ?>>
                    <?= $l['levelName']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Gender:</label>
        <select name="Gender" class="form-control" required>
            <option <?= ($emp['Gender']=="Male")?"selected":""; ?>>Male</option>
            <option <?= ($emp['Gender']=="Female")?"selected":""; ?>>Female</option>
            <option <?= ($emp['Gender']=="Other")?"selected":""; ?>>Other</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="Email" value="<?= $emp['Email']; ?>" class="form-control" required>
    </div>

    <button class="btn btn-primary">Update Employee</button>
    <a href="employee_view.php" class="btn btn-secondary">Back</a>

</form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#designation').select2();
    $('#level').select2();
});
</script>
