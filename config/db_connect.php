<?php
/**
 * Database Connection File
 * Uses PDO for secure database access
 */

// Database credentials
$host = 'localhost';
$dbname = 'vendorbridge_db';
$username = 'root';
$password = ''; // empty password for default XAMPP

try {
    // Create PDO connection string (DSN)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    // Instantiate PDO connection
    $pdo = new PDO($dsn, $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle connection errors gracefully
    die("Database Connection Failed: " . $e->getMessage());
}
?>
