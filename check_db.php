<?php
include 'includes/db.php';

try {
    // Check columns in the registrations table
    $stmt = $pdo->query("DESCRIBE registrations");
    echo "<h2>Registrations Table Structure:</h2>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
