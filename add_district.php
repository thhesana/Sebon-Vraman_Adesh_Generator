<?php
include 'db.php';
include 'header.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $district_name = trim($_POST['district_name']);
    $district_name_nepali = trim($_POST['district_name_nepali']);

    /* =========================
       Validation
    ========================= */
    if ($district_name === "") {
        $errors[] = "District name (English) is required.";
    }

    if ($district_name_nepali === "") {
        $errors[] = "District name (Nepali) is required.";
    }

    /* =========================
       Duplicate Check
    ========================= */
    if (empty($errors)) {
        $check_sql = "
            SELECT COUNT(*) AS total
            FROM DistrictMaster
            WHERE District_name = ? OR District_name_nepali = ?
        ";
        $check_params = [$district_name, $district_name_nepali];
        $check_stmt = sqlsrv_query($conn, $check_sql, $check_params);
        $check_row = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

        if ($check_row['total'] > 0) {
            $errors[] = "District already exists.";
        }
    }

    /* =========================
       Insert Data
    ========================= */
    if (empty($errors)) {
        $insert_sql = "
            INSERT INTO DistrictMaster (District_name, District_name_nepali)
            VALUES (?, ?)
        ";
        $insert_params = [$district_name, $district_name_nepali];
        $insert_stmt = sqlsrv_query($conn, $insert_sql, $insert_params);

        if ($insert_stmt) {
            $success = "District added successfully.";
            $district_name = "";
            $district_name_nepali = "";
        } else {
            $errors[] = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add New District</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f7fa;
        }

        .container {
            width: 45%;
            margin: 40px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #004080;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .btn-group {
            text-align: center;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-save {
            background-color: #004080;
            color: #ffffff;
        }

        .btn-save:hover {
            background-color: #003060;
        }

        .btn-back {
            background-color: #6c757d;
            color: #ffffff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Add New District</h2>

    <!-- Messages -->
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo "• " . htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST">
        <div class="form-group">
            <label>District Name (English)</label>
            <input type="text" name="district_name"
                   value="<?= htmlspecialchars($district_name ?? '') ?>"
                   required>
        </div>

        <div class="form-group">
            <label>District Name (Nepali)</label>
            <input type="text" name="district_name_nepali"
                   value="<?= htmlspecialchars($district_name_nepali ?? '') ?>"
                   required>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-save">Save</button>
            <a href="district_list.php" class="btn-back">Back</a>
        </div>
    </form>

</div>

</body>
</html>

<?php sqlsrv_close($conn); ?>
