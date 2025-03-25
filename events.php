<?php
session_start();
include 'includes/db.php';

// Validate and sanitize filter parameters
$search = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_STRING)) : '';
$category = isset($_GET['category']) ? trim(filter_var($_GET['category'], FILTER_SANITIZE_STRING)) : '';
$date = isset($_GET['date']) ? trim(filter_var($_GET['date'], FILTER_SANITIZE_STRING)) : '';
// Only apply price filter if explicitly provided
$price_max = array_key_exists('price_max', $_GET) ? (int)$_GET['price_max'] : null;
$price_display = $price_max !== null ? $price_max : 1000;

// Get categories for filter
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM events ORDER BY category");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Category Fetch Error: " . $e->getMessage());
    $categories = [];
}

try {
    // Build dynamic WHERE clause without fixed date condition so all events show
    $sql = "SELECT e.*, u.name as organizer_name FROM events e LEFT JOIN users u ON e.organizer_id = u.user_id";
    $conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($category)) {
        $conditions[] = "e.category = ?";
        $params[] = $category;
    }
    
    if (!empty($date)) {
        $conditions[] = "DATE(e.date) = ?";
        $params[] = $date;
    }
    
    if ($price_max !== null && $price_max > 0) {
        $conditions[] = "e.ticket_price <= ?";
        $params[] = $price_max;
    }
    
    if(count($conditions) > 0){
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY e.date ASC";

    // Fetch events
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$events) {
        error_log("No events found with the given filters.");
    }
} catch (PDOException $e) {
    error_log("Event Search Error: " . $e->getMessage());
    $events = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Events - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-calendar-check me-2"></i>Event Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link hover-effect" href="admin/login.php">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hover-effect" href="organizer/login.php">
                            <i class="fas fa-calendar-alt"></i> Organizer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link hover-effect" href="attendee/login.php">
                            <i class="fas fa-user"></i> User
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link hover-effect" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        <?php else: ?>
                            <a class="nav-link hover-effect" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4 border-bottom pb-3">
            <div class="col-md-8">
                <h2 class="h2 text-dark">All Events</h2>
                <p class="text-muted">Browse through our upcoming events</p>
            </div>
            <div class="col-md-4 text-end">
                <button id="toggleFilter" class="btn btn-secondary btn-lg">
                    <i class="fas fa-filter"></i> Show Filters
                </button>
            </div>
        </div>

        <!-- Advanced Filter Section -->
        <div class="filter-section mb-4 p-4 border rounded bg-white" id="filterSection" style="display: none;">
            <form method="GET" action="events.php">
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-bold">
                            <i class="fas fa-search text-primary me-2"></i>Search
                        </label>
                        <input type="text" id="search" name="search" class="form-control form-control-lg" 
                               placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label fw-bold">
                            <i class="fas fa-tag text-primary me-2"></i>Category
                        </label>
                        <select id="category" name="category" class="form-select form-select-lg">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($cat)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label fw-bold">
                            <i class="fas fa-calendar text-primary me-2"></i>Date
                        </label>
                        <input type="date" id="date" name="date" class="form-control form-control-lg" value="<?= $date ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="priceFilter" class="form-label fw-bold">
                            <i class="fas fa-dollar-sign text-primary me-2"></i>Max Price: $<span id="priceValue"><?= $price_display ?></span>
                        </label>
                        <input type="range" class="form-range" min="0" max="1000" step="10" 
                               value="<?= $price_display ?>" id="priceFilter" name="price_max">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Events Display -->
        <div class="row g-4">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <div class="alert alert-secondary">No events found matching your criteria</div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    $image_path = $event['image_path'] ?? 'assets/images/events/default.jpg';
                    if (!filter_var($image_path, FILTER_VALIDATE_URL)) {
                        $image_path = str_replace('../', '', $image_path);
                    }
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border shadow-sm">
                        <div class="position-relative">
                            <img src="<?= htmlspecialchars($image_path) ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;"
                                 alt="<?= htmlspecialchars($event['title']) ?>">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-secondary">
                                <?= ucfirst($event['category']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-dark mb-3 border-bottom pb-2">
                                <?= htmlspecialchars($event['title']) ?>
                            </h5>
                            <div class="card-text text-muted small mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar-day fa-fw me-2"></i>
                                    <span><?= date('l, F j, Y', strtotime($event['date'])) ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-map-marker-alt fa-fw me-2"></i>
                                    <span><?= htmlspecialchars($event['location']) ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-ticket-alt fa-fw me-2"></i>
                                    <span>Price: $<?= number_format($event['ticket_price'], 2) ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-users fa-fw me-2"></i>
                                    <span>Total Tickets: <?= number_format($event['total_tickets']) ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-tie fa-fw me-2"></i>
                                    <span><?= htmlspecialchars($event['organizer_name'] ?? 'Unknown') ?></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <a href="event_details.php?id=<?= $event['event_id'] ?>" 
                                   class="btn btn-outline-secondary btn-sm w-100">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
