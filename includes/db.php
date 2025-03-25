<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'event';

// Create MySQLi connection (for backward compatibility)
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

try {
    // Establish the database connection using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>