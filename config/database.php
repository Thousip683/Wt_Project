<?php
/**
 * Database Configuration
 * Beyond Classroom - Student Academic Management Platform
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Thousip*786');
define('DB_NAME', 'beyond_classroom');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for better unicode support
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Function to close connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
