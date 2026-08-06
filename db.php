<?php
// Start session BEFORE anything else
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$serverName = "DESKTOP-IGPDORH";
$connectionOptions = array(
    "Database" => "Vraman_Adesh_Generator",
    "Uid" => "sa",
    "PWD" => "Lazy-Car92",
    "CharacterSet" => "UTF-8"
);

// Create connection
if (!isset($conn) || $conn === false) {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn === false) {
        die("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
            <h3>❌ Database Connection Failed!</h3>
            <pre>" . print_r(sqlsrv_errors(), true) . "</pre>
            </div>");
    }
}
?>