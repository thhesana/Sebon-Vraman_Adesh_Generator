<?php
include 'db.php';
include 'HEADER.php';

$sql = "
SELECT 
    EmpPersonalCode,
    EmpNameInNepali,
    EmpName,
    Designation,
    LevelName,
    Gender,
    Email
FROM Employee_Information
ORDER BY EmpName ASC
";

$stmt = sqlsrv_query($conn, $sql);
?>

<h2 class="text-center mt-4">Employee List</h2>
<div class="container mt-3">

    <a href="employee_add.php" class="btn btn-success mb-3 float-end">Add New Employee</a>

    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search employees...">

    <table class="table table-bordered table-striped" id="empTable">
        <thead class="bg-primary text-white">
            <tr>
                <th>S.N.</th>
                <th style="display:none;">Code</th>
                <th>Name</th>
                <th>Name (Nepali)</th>
                <th>Designation</th>
                <th>Level</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $sn = 1; while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= $sn++; ?></td>
                    <td style="display:none;"><?= $row['EmpPersonalCode']; ?></td>
                  
                    <td><?= $row['EmpName']; ?></td>
					  <td><?= $row['EmpNameInNepali']; ?></td>
                    <td><?= $row['Designation']; ?></td>
                    <td><?= $row['LevelName']; ?></td>
                    <td><?= $row['Gender']; ?></td>
                    <td><?= $row['Email']; ?></td>
                    <td>
                        <a href="employee_edit.php?code=<?= $row['EmpPersonalCode']; ?>" 
                           class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#empTable tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>
