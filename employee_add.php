<?php
include 'db.php';
include 'HEADER.php';

// Designation dropdown
$designationSql = "SELECT id, designationType FROM DesignationTypeMaster ORDER BY designationType";
$designationStmt = sqlsrv_query($conn, $designationSql);

// Level dropdown
$levelSql = "SELECT id, levelName FROM LevelNameMaster ORDER BY levelName";
$levelStmt = sqlsrv_query($conn, $levelSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['EmpPersonalCode'];
    $nep = $_POST['EmpNameInNepali'];
    $eng = $_POST['EmpName'];
    $desig = $_POST['Designation'];
    $level = $_POST['LevelName'];
    $gender = $_POST['Gender'];
    $email = $_POST['Email'];

    $sqlInsert = "
        INSERT INTO Employee_Information 
        (EmpPersonalCode, EmpNameInNepali, EmpName, Designation, LevelName, Gender, Email)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $params = [$code, $nep, $eng, $desig, $level, $gender, $email];

    if (sqlsrv_query($conn, $sqlInsert, $params)) {
        echo "<script>alert('Employee Added Successfully');window.location='employee_view.php';</script>";
    } else {
        print_r(sqlsrv_errors());
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<h2 class="text-center mt-4">Add Employee</h2>
<div class="container mt-3">
<form method="POST">

    <div class="mb-3">
        <label>Employee Code:</label>
        <input type="text" name="EmpPersonalCode" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Name in Nepali:</label>
        <input type="text" name="EmpNameInNepali" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Name in English:</label>
        <input type="text" name="EmpName" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Designation:</label>
        <select name="Designation" id="designation" class="form-control" required>
            <option value="">-- Select Designation --</option>
            <?php while($d = sqlsrv_fetch_array($designationStmt, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $d['designationType']; ?>"><?= $d['designationType']; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Level:</label>
        <select name="LevelName" id="level" class="form-control" required>
            <option value="">-- Select Level --</option>
            <?php while($l = sqlsrv_fetch_array($levelStmt, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $l['levelName']; ?>"><?= $l['levelName']; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Gender:</label>
        <select name="Gender" class="form-control" required>
            <option value="">-- Select Gender --</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="Email" class="form-control" required>
    </div>

    <button class="btn btn-primary">Save Employee</button>
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
