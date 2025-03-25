<?php
require 'includes/db.php';

echo "<h1>Fix event_requests Table for Admin Modification Feature</h1>";

// Check if event_requests table exists
$checkTable = "SHOW TABLES LIKE 'event_requests'";
$result = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color:green'>The event_requests table exists, checking required columns...</p>";
    
    // Array of required columns for admin modification feature
    $requiredColumns = [
        'is_admin_modified' => "ALTER TABLE event_requests ADD COLUMN is_admin_modified tinyint(1) NOT NULL DEFAULT 0 AFTER assigned_by",
        'price_quote' => "ALTER TABLE event_requests ADD COLUMN price_quote decimal(10,2) DEFAULT NULL AFTER event_budget",
        'user_price_counter' => "ALTER TABLE event_requests ADD COLUMN user_price_counter decimal(10,2) DEFAULT NULL AFTER price_quote",
        'admin_comments' => "ALTER TABLE event_requests ADD COLUMN admin_comments text DEFAULT NULL AFTER status",
        'attendee_response' => "ALTER TABLE event_requests ADD COLUMN attendee_response text DEFAULT NULL AFTER is_admin_modified",
        'updated_at' => "ALTER TABLE event_requests ADD COLUMN updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()"
    ];
    
    // Check for main id column name and fix if needed
    $checkIdColumn = "SHOW COLUMNS FROM event_requests LIKE 'request_id'";
    $idColumnResult = mysqli_query($conn, $checkIdColumn);
    
    if (mysqli_num_rows($idColumnResult) > 0) {
        echo "<p style='color:blue'>Found 'request_id' column, checking if rename to 'id' is needed...</p>";
        
        // Check if 'id' already exists to avoid conflicts
        $checkNewIdColumn = "SHOW COLUMNS FROM event_requests LIKE 'id'";
        $newIdColumnResult = mysqli_query($conn, $checkNewIdColumn);
        
        if (mysqli_num_rows($newIdColumnResult) == 0) {
            $renameColumn = "ALTER TABLE event_requests CHANGE request_id id int(11) NOT NULL AUTO_INCREMENT";
            if (mysqli_query($conn, $renameColumn)) {
                echo "<p style='color:green'>Successfully renamed 'request_id' to 'id'</p>";
            } else {
                echo "<p style='color:red'>Error renaming column: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color:blue'>Both 'id' and 'request_id' exist, no rename needed</p>";
        }
    }
    
    // Check if total_price should be renamed to event_budget
    $checkPriceColumn = "SHOW COLUMNS FROM event_requests LIKE 'total_price'";
    $priceColumnResult = mysqli_query($conn, $checkPriceColumn);
    
    if (mysqli_num_rows($priceColumnResult) > 0) {
        $checkBudgetColumn = "SHOW COLUMNS FROM event_requests LIKE 'event_budget'";
        $budgetColumnResult = mysqli_query($conn, $checkBudgetColumn);
        
        if (mysqli_num_rows($budgetColumnResult) == 0) {
            $renamePriceColumn = "ALTER TABLE event_requests CHANGE total_price event_budget decimal(10,2) NOT NULL DEFAULT 0.00";
            if (mysqli_query($conn, $renamePriceColumn)) {
                echo "<p style='color:green'>Successfully renamed 'total_price' to 'event_budget'</p>";
            } else {
                echo "<p style='color:red'>Error renaming price column: " . mysqli_error($conn) . "</p>";
            }
        }
    }
    
    // Check and update status column to support all needed values
    $checkStatusColumn = "SHOW COLUMNS FROM event_requests WHERE Field = 'status'";
    $statusColumnResult = mysqli_query($conn, $checkStatusColumn);
    
    if ($statusColumnResult && mysqli_num_rows($statusColumnResult) > 0) {
        $statusColumn = mysqli_fetch_assoc($statusColumnResult);
        
        // If status is enum type, change to varchar to support more values
        if (strpos($statusColumn['Type'], 'enum') !== false) {
            $updateStatusColumn = "ALTER TABLE event_requests MODIFY COLUMN status varchar(20) NOT NULL DEFAULT 'pending'";
            if (mysqli_query($conn, $updateStatusColumn)) {
                echo "<p style='color:green'>Successfully updated status column to varchar to support more status values</p>";
            } else {
                echo "<p style='color:red'>Error updating status column: " . mysqli_error($conn) . "</p>";
            }
        }
    }
    
    // Check each required column and add if missing
    foreach ($requiredColumns as $column => $query) {
        $checkColumn = "SHOW COLUMNS FROM event_requests LIKE '$column'";
        $columnResult = mysqli_query($conn, $checkColumn);
        
        if (mysqli_num_rows($columnResult) == 0) {
            // Column doesn't exist, add it
            if (mysqli_query($conn, $query)) {
                echo "<p style='color:green'>Successfully added $column column to event_requests table</p>";
            } else {
                echo "<p style='color:red'>Error adding $column column: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color:blue'>The $column column already exists</p>";
        }
    }
} else {
    // Table doesn't exist, redirect to fix_database.php
    echo "<p style='color:red'>The event_requests table doesn't exist. Please run fix_database.php first.</p>";
    echo "<p><a href='fix_database.php' class='btn btn-primary'>Run Database Fix</a></p>";
}

// Check if request_modifications table exists
$checkModTable = "SHOW TABLES LIKE 'request_modifications'";
$modResult = mysqli_query($conn, $checkModTable);

if (mysqli_num_rows($modResult) == 0) {
    // Create request_modifications table
    $createModTable = "CREATE TABLE `request_modifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request_id` int(11) NOT NULL,
        `modified_by` int(11) NOT NULL,
        `modification_note` text NOT NULL,
        `previous_status` varchar(50) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `request_id_index` (`request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (mysqli_query($conn, $createModTable)) {
        echo "<p style='color:green'>Successfully created request_modifications table</p>";
    } else {
        echo "<p style='color:red'>Error creating request_modifications table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue'>The request_modifications table already exists</p>";
}

// Show current table structure for verification
echo "<h2>Current event_requests Table Structure</h2>";
$descTable = "DESCRIBE event_requests";
$descResult = mysqli_query($conn, $descTable);

if ($descResult) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($descResult)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red'>Error describing table: " . mysqli_error($conn) . "</p>";
}

echo "<br><p style='margin-top: 20px;'>";
echo "<a href='attendee/dashboard.php' class='btn btn-primary'>Go to Attendee Dashboard</a> ";
echo "<a href='admin/manage_event_requests.php' class='btn btn-success'>Go to Admin Event Requests</a>";
echo "</p>";
?>
