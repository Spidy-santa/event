<?php
session_start();
require '../includes/db.php';

// Check if the connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Restrict to admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize message variables
$successMsg = "";
$errorMsg = "";

// Initialize default service details
$serviceDetails = [
    // Wedding services
    'venue_wedding' => ['name' => 'Venue Rental', 'price' => 50000, 'is_per_person' => false],
    'decoration_wedding' => ['name' => 'Decoration/Setting', 'price' => 25000, 'is_per_person' => false],
    'food_veg' => ['name' => 'Food & Beverage (Vegetarian)', 'price' => 700, 'is_per_person' => true],
    'food_nonveg' => ['name' => 'Food & Beverage (Non-Vegetarian)', 'price' => 900, 'is_per_person' => true],
    'photography' => ['name' => 'Photography Package', 'price' => 15000, 'is_per_person' => false],
    'entertainment_wedding' => ['name' => 'Entertainment', 'price' => 20000, 'is_per_person' => false],
    
    // Birthday services
    'venue_birthday' => ['name' => 'Venue/Setting', 'price' => 20000, 'is_per_person' => false],
    'cake' => ['name' => 'Cake', 'price' => 3000, 'is_per_person' => false],
    'decoration_birthday' => ['name' => 'Decoration', 'price' => 10000, 'is_per_person' => false],
    'entertainment_birthday' => ['name' => 'Entertainment/DJ', 'price' => 15000, 'is_per_person' => false],
    'food_birthday' => ['name' => 'Food', 'price' => 600, 'is_per_person' => true],
    
    // Conference services
    'venue_conference' => ['name' => 'Venue Rental', 'price' => 40000, 'is_per_person' => false],
    'av_equipment' => ['name' => 'Audio/Visual Equipment', 'price' => 25000, 'is_per_person' => false],
    'catering_conference' => ['name' => 'Catering', 'price' => 750, 'is_per_person' => true],
    'speaker_fee' => ['name' => 'Speaker/Program Fee', 'price' => 30000, 'is_per_person' => false],
    
    // Workshop services
    'venue_workshop' => ['name' => 'Venue', 'price' => 20000, 'is_per_person' => false],
    'materials' => ['name' => 'Materials/Equipment', 'price' => 400, 'is_per_person' => true],
    'instructor' => ['name' => 'Instructor/Facilitator', 'price' => 25000, 'is_per_person' => false],
    'refreshments' => ['name' => 'Refreshments', 'price' => 300, 'is_per_person' => true]
];

// Create table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS service_prices (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_per_person BOOLEAN NOT NULL
)";

if (!mysqli_query($conn, $createTableQuery)) {
    $errorMsg = "Error creating table: " . mysqli_error($conn);
}

// Check if we have data in the table, if not insert default values
$checkQuery = "SELECT COUNT(*) as count FROM service_prices";
$result = mysqli_query($conn, $checkQuery);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // Insert default values
    foreach ($serviceDetails as $id => $service) {
        $insertQuery = "INSERT INTO service_prices (id, name, price, is_per_person) 
                       VALUES (?, ?, ?, ?)";
                       
        $stmt = mysqli_prepare($conn, $insertQuery);
        $isPerPerson = $service['is_per_person'] ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "ssdi", $id, $service['name'], $service['price'], $isPerPerson);
        
        if (!mysqli_stmt_execute($stmt)) {
            $errorMsg = "Error inserting default values: " . mysqli_error($conn);
            break;
        }
    }
    
    if (empty($errorMsg)) {
        $successMsg = "Default service prices have been initialized.";
    }
}

// Process form submission for price updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_prices'])) {
        $updateSuccess = true;
        
        foreach ($_POST['price'] as $id => $price) {
            $updateQuery = "UPDATE service_prices SET price = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ds", $price, $id);
            
            if (!mysqli_stmt_execute($stmt)) {
                $errorMsg = "Error updating prices: " . mysqli_error($conn);
                $updateSuccess = false;
                break;
            }
        }
        
        if ($updateSuccess) {
            $successMsg = "Service prices have been updated successfully.";
        }
    }
}

// Fetch current service prices
$query = "SELECT * FROM service_prices ORDER BY name";
$result = mysqli_query($conn, $query);

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[$row['id']] = $row;
}

// Group services by category
$categories = [
    'wedding' => ['title' => 'Wedding Services', 'services' => []],
    'birthday' => ['title' => 'Birthday Party Services', 'services' => []],
    'conference' => ['title' => 'Conference Services', 'services' => []],
    'workshop' => ['title' => 'Workshop Services', 'services' => []]
];

// Assign services to their categories
foreach ($services as $id => $service) {
    if (strpos($id, 'wedding') !== false || in_array($id, ['photography', 'food_veg', 'food_nonveg'])) {
        $categories['wedding']['services'][$id] = $service;
    } elseif (strpos($id, 'birthday') !== false || in_array($id, ['cake'])) {
        $categories['birthday']['services'][$id] = $service;
    } elseif (strpos($id, 'conference') !== false || in_array($id, ['speaker_fee'])) {
        $categories['conference']['services'][$id] = $service;
    } elseif (strpos($id, 'workshop') !== false || in_array($id, ['materials', 'instructor', 'refreshments'])) {
        $categories['workshop']['services'][$id] = $service;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Service Prices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .price-card {
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        .price-card .card-header {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .per-person-badge {
            background-color: #0d6efd;
        }
        .fixed-price-badge {
            background-color: #198754;
        }
        .success-message {
            color: #198754;
            margin-bottom: 20px;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="container mt-5">
    <h2 class="mb-4">Manage Service Prices</h2>
    
    <?php if(!empty($successMsg)): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <?php if(!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="row">
            <?php foreach($categories as $categoryId => $category): ?>
                <div class="col-md-6 mb-4">
                    <div class="card price-card">
                        <div class="card-header">
                            <?php echo $category['title']; ?>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Type</th>
                                        <th>Price (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($category['services'] as $id => $service): ?>
                                        <tr>
                                            <td><?php echo $service['name']; ?></td>
                                            <td>
                                                <?php if($service['is_per_person']): ?>
                                                    <span class="badge per-person-badge">Per Person</span>
                                                <?php else: ?>
                                                    <span class="badge fixed-price-badge">Fixed Price</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="price[<?php echo $id; ?>]" 
                                                       value="<?php echo $service['price']; ?>" 
                                                       min="0" 
                                                       step="50"
                                                       required>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="d-flex justify-content-between mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button type="submit" name="update_prices" class="btn btn-primary">Update Prices</button>
        </div>
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
