<?php
// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=event', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create budget_items table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS budget_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        event_id INT(11) NOT NULL,
        category VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at DATETIME NOT NULL,
        INDEX (event_id)
    )";
    
    $pdo->exec($sql);
    echo "Budget items table created or verified successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
