<?php
// dbconn.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ttt_zoom';

// Create connection with error handling
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for proper encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error to file for production
    error_log("Database Error: " . $e->getMessage());
    
    // User-friendly message
    die("System temporarily unavailable. Please try again later.");
}
?>