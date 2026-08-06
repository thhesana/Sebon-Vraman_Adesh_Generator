<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEBON MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f9f9f9;
            font-family: 'Arial', sans-serif;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 30px 15px;
            text-align: center;
        }
        .header h2 {
            margin-bottom: 20px;
        }
        .user-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .logout-btn {
            background: red;
            border: none;
            padding: 8px 15px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: darkred;
        }
        .nav-tabs .nav-link {
            color: white;
        }
        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active {
            background: #0056b3;
        }
        /* Loading spinner */
        .spinner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .spinner-overlay.active {
            display: flex;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
		
		.nav-item {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    min-width: 220px;
    padding: 0;
    margin: 0;
    list-style: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 999;
}

.dropdown-menu li a {
    display: block;
    padding: 10px 15px;
    color: #000;
    text-decoration: none;
}

.dropdown-menu li a:hover {
    background: #f2f2f2;
}

.nav-item:hover .dropdown-menu {
    display: block;
}
    </style>
</head>
<body>
<!-- Loading Spinner -->
<div class="spinner-overlay" id="loadingSpinner">
    <div class="spinner"></div>
</div>

<div class="header">
    <h2>VRAMAN ADESH GENERATOR</h2>
    <div class="user-info">
        <p class="mb-0">User: <strong><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <form method="POST" style="display:inline;">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>
</div>
<ul class="nav nav-tabs bg-primary justify-content-center">
    <li class="nav-item">
        <a href="dashboard.php" class="nav-link text-white">DASHBOARD</a>
    </li>
    <li class="nav-item">
        <a href="InternationalVraman.php" class="nav-link text-white" id="intlVramanTab">INT'L VRAMAN</a>
    </li>
	<li class="nav-item">
        <a href="DomesticTadaView.php" class="nav-link text-white" id="intlVramanTab">DOMESTIC VRAMAN</a>
    </li>
    <li class="nav-item">
        <a href="usd_rate.php" class="nav-link text-white">USD RATE</a>
    </li>
    <li class="nav-item">
        <a href="orderlevel.php" class="nav-link text-white">LEVEL_MASTER</a>
    </li>
	<li class="nav-item">
        <a href="countrylist.php" class="nav-link text-white">COUNTRY</a>
    </li>
	<li class="nav-item">
        <a href="cityLIst.php" class="nav-link text-white">CITY</a>
    </li>
	<li class="nav-item">
        <a href="DistrictList.php" class="nav-link text-white">DISTRICT</a>
    </li>
	<li class="nav-item">
        <a href="employee_view.php" class="nav-link text-white">EMPLOYEE</a>
    </li>
	<li class="nav-item">
        <a href="fiscal_year.php" class="nav-link text-white">FY</a>
    </li>
	<li class="nav-item dropdown">
    <a href="#" class="nav-link text-white">REPORT</a>

    <ul class="dropdown-menu">
        <li><a href="DOMESTIC_TADAREPORT.php">Domestic Vraman Report</a></li>
        <li><a href="INTERNATIONAL_TADAREPORT.php">Int'l Vraman Report</a></li>
    </ul>
</li>
</ul>

<script>
// Get the tab element
const intlTab = document.getElementById('intlVramanTab');
const spinner = document.getElementById('loadingSpinner');

intlTab.addEventListener('click', function(e) {
    e.preventDefault(); // Prevent immediate navigation
    
    // Show loading spinner
    spinner.classList.add('active');
    
    // Run usdforexudater.php in the background
    fetch('usdforexudater.php')
        .then(response => {
            if (response.ok) {
                console.log('usdforexudater.php triggered successfully');
            } else {
                console.warn('usdforexudater.php returned an error status');
            }
            // Navigate AFTER the fetch completes
            window.location.href = 'InternationalVraman.php';
        })
        .catch(error => {
            console.error('Error triggering usdforexudater.php:', error);
            // Navigate even if there's an error (so user doesn't get stuck)
            window.location.href = 'InternationalVraman.php';
        })
        .finally(() => {
            // Hide spinner (though page will navigate away)
            spinner.classList.remove('active');
        });
});
</script>
</body>
</html>