<?php
// Path to your Python script
$pythonScript = 'C:\\xampp\\htdocs\\Vraman_Adesh_Generator\\domestic_mail_notifier.py';

// Command to run in background
$command = "start /B python \"$pythonScript\"";

// Execute the command
exec($command);

// Return a response immediately
echo json_encode(['status' => 'success', 'message' => 'Batch creation started.']);
