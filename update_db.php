<?php
require 'includes/db.php';

echo "<h1>Database Update for Event Management System</h1>";

// Read the SQL file
$sql = file_get_contents('update_db_event_requests.sql');

// Split into individual queries
$queries = explode(';', $sql);

// Execute each query
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>✓ Successfully executed query</p>";
    } else {
        echo "<p style='color: red;'>✗ Error executing query: " . mysqli_error($conn) . "</p>";
        echo "<pre>" . htmlentities($query) . "</pre>";
        $success = false;
    }
}

if ($success) {
    echo "<p style='color: green; font-weight: bold;'>Database update completed successfully!</p>";
    echo "<p>You can now use the enhanced event request form with dynamic category-specific fields and per-person pricing.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Database update encountered errors. Please check the output above.</p>";
}

echo "<p><a href='attendee/request_event.php'>Go to Event Request Form</a></p>";
?>
