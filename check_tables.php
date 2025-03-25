<?php
// Include database connection
require_once 'includes/db.php';

// Check events table structure
echo "<h3>Events Table Structure:</h3>";
try {
    $result = $pdo->query("SHOW COLUMNS FROM events");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Check event_requests table structure
echo "<h3>Event Requests Table Structure:</h3>";
try {
    $result = $pdo->query("SHOW COLUMNS FROM event_requests");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
