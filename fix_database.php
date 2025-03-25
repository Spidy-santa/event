<?php
require 'includes/db.php';

echo "<h1>Database Fix for Event Management System</h1>";

// Fix the database structure
$queries = [
    // Create the event_requests table without foreign key constraints
    "CREATE TABLE IF NOT EXISTS `event_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `name` varchar(100) NOT NULL,
        `address` text NOT NULL,
        `contact` varchar(20) NOT NULL,
        `event_type` varchar(50) NOT NULL,
        `event_details` text NOT NULL,
        `preferred_date` date NOT NULL,
        `location` varchar(255) NOT NULL,
        `total_attendees` int(11) NOT NULL,
        `selected_options` text COMMENT 'JSON encoded selected services with pricing',
        `event_budget` decimal(10,2) NOT NULL DEFAULT 0.00,
        `price_quote` decimal(10,2) DEFAULT NULL,
        `user_price_counter` decimal(10,2) DEFAULT NULL,
        `status` varchar(20) NOT NULL DEFAULT 'pending',
        `admin_comments` text DEFAULT NULL,
        `assigned_by` varchar(20) DEFAULT NULL,
        `is_admin_modified` tinyint(1) NOT NULL DEFAULT 0,
        `attendee_response` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    // Create service_prices table
    "CREATE TABLE IF NOT EXISTS `service_prices` (
        `id` varchar(50) PRIMARY KEY,
        `name` varchar(100) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `is_per_person` tinyint(1) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    // Create request_modifications table to track changes
    "CREATE TABLE IF NOT EXISTS `request_modifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request_id` int(11) NOT NULL,
        `modified_by` int(11) NOT NULL,
        `modification_note` text NOT NULL,
        `previous_status` varchar(50) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    // Ensure status column supports all needed values
    "ALTER TABLE `event_requests` MODIFY COLUMN `status` varchar(20) NOT NULL DEFAULT 'pending';"
];

// Execute each query
$success = true;
foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>✓ Successfully executed query</p>";
        echo "<pre>" . htmlentities($query) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Error executing query: " . mysqli_error($conn) . "</p>";
        echo "<pre>" . htmlentities($query) . "</pre>";
        $success = false;
    }
}

// Add index on common columns for better performance
$indexQueries = [
    "ALTER TABLE `event_requests` ADD INDEX `user_id_index` (`user_id`);",
    "ALTER TABLE `event_requests` ADD INDEX `status_index` (`status`);",
    "ALTER TABLE `request_modifications` ADD INDEX `request_id_index` (`request_id`);"
];

echo "<h2>Adding database indexes:</h2>";
foreach ($indexQueries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>✓ Successfully added index</p>";
    } else {
        // If index already exists, this is not a critical error
        echo "<p style='color: orange;'>⚠ Index may already exist: " . mysqli_error($conn) . "</p>";
    }
}

if ($success) {
    echo "<p style='color: green; font-weight: bold;'>Database fix completed successfully!</p>";
    echo "<p>You can now use the event request form and admin modification features without errors.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Database fix encountered errors. Please check the output above.</p>";
}

echo "<p><a href='attendee/dashboard.php'>Go to Attendee Dashboard</a></p>";
echo "<p><a href='admin/manage_event_requests.php'>Go to Admin Event Requests</a></p>";
?>
