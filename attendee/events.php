<?php
session_start();
require '../includes/db.php';

// Restrict to attendees
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendee') {
    header("Location: ../login.php");
    exit();
}

// Include functions
$functions_file = __DIR__ . '/../includes/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
} else {
    // If the file doesn't exist, define essential functions to prevent fatal errors
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    function get_recommended_events($user_id) {
        return [];
    }
    
    function get_popular_categories() {
        return [];
    }
    
    // Log the error
    error_log("Missing functions.php file at: " . $functions_file);
}

// AI-Powered Recommendations
function get_recommended_events($user_id) {
  global $pdo;
  try {
    $stmt = $pdo->prepare("SELECT * FROM events
                        WHERE category IN (
                          SELECT category FROM registrations
                          JOIN events ON registrations.event_id = events.event_id
                          WHERE user_id = ?
                        ) AND date >= CURDATE()
                        ORDER BY RAND() LIMIT 5");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
  } catch(PDOException $e) {
    error_log("Recommendation Error: " . $e->getMessage());
    return [];
  }
}

// Get popular categories function
function get_popular_categories() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count 
                             FROM events 
                             GROUP BY category 
                             ORDER BY count DESC 
                             LIMIT 10");
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'category');
    } catch(PDOException $e) {
        error_log("Category Error: " . $e->getMessage());
        return [];
    }
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 1000;

try {
    // Base query
    $sql = "SELECT * FROM events WHERE date >= CURDATE()";
    $params = [];

    // Add search filters
    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($category) && in_array($category, get_popular_categories())) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    // Add price filter
    if ($price_max > 0) {
        $sql .= " AND ticket_price <= ?";
        $params[] = $price_max;
    }

    $sql .= " ORDER BY date ASC";

    // Fetch events
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
    // Get recommended events if user is logged in
    $recommended_events = [];
    if (isset($_SESSION['user_id'])) {
        $recommended_events = get_recommended_events($_SESSION['user_id']);
    }

} catch(PDOException $e) {
    error_log("Event Search Error: " . $e->getMessage());
    $events = [];
    $recommended_events = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .event-card {
            transition: transform 0.2s;
            min-height: 300px;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .price-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .category-cloud .tag {
            display: inline-block;
            margin: 5px;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
        }
        .category-cloud .tag:hover {
            background: #e0e0e0;
        }
        .recommended-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="container mt-5">
    <h1 class="mb-4">Browse Events</h1>

    <!-- Search & Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach(get_popular_categories() as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="price-range">
                            <label for="priceFilter" class="form-label">Max Price: $<span id="priceValue"><?= $price_max ?></span></label>
                            <input type="range" class="form-range" min="0" max="1000" step="10" 
                                   value="<?= $price_max ?>" id="priceFilter" name="price_max">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Category Cloud -->
    <div class="category-cloud mb-4 text-center">
        <?php foreach(get_popular_categories() as $cat): ?>
        <a href="?category=<?= urlencode($cat) ?>" class="tag">
            <?= htmlspecialchars($cat) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Recommended Events -->
    <?php if(!empty($recommended_events)): ?>
    <div class="recommended-section">
        <h3><i class="bi bi-lightbulb"></i> Recommended For You</h3>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($recommended_events as $event): ?>
            <div class="col">
                <div class="card event-card h-100">
                    <?php if($event['image_url']): ?>
                    <img src="<?= htmlspecialchars($event['image_url']) ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <span class="badge bg-success price-badge">
                            $<?= number_format($event['ticket_price'], 2) ?>
                        </span>
                        
                        <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                        <p class="card-text text-muted">
                            <i class="bi bi-calendar-event"></i>
                            <?= date('M j, Y', strtotime($event['date'])) ?>
                        </p>
                        <p class="card-text"><?= htmlspecialchars($event['location']) ?></p>
                        <p class="card-text"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</p>
                        
                        <div class="mt-auto">
                            <a href="event_details.php?id=<?= $event['event_id'] ?>" 
                               class="btn btn-success">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Event Requests -->
    <?php
    $userId = $_SESSION['user_id'];
    $query = "SELECT * FROM event_requests WHERE user_id = '$userId'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo "<h3>Your Event Requests</h3>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='event-request'>";
            echo "<p>Event Details: " . $row['event_details'] . "</p>";
            echo "<p>Preferred Date: " . $row['preferred_date'] . "</p>";
            echo "<p>Status: " . ucfirst($row['status']) . "</p>";
            if ($row['status'] === 'approved' && $row['assigned_organizer_id']) {
                echo "<p>Assigned Organizer: " . $row['assigned_organizer_id'] . "</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No event requests found.</p>";
    }
    ?>

    <!-- Event Grid -->
    <h3 class="mb-3">All Events</h3>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if(empty($events)): ?>
        <div class="col">
            <div class="alert alert-info">No events found matching your criteria</div>
        </div>
        <?php else: ?>
        <?php foreach($events as $event): ?>
        <div class="col">
            <div class="card event-card h-100">
                <?php if($event['image_url']): ?>
                <img src="<?= htmlspecialchars($event['image_url']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($event['title']) ?>">
                <?php endif; ?>
                
                <div class="card-body">
                    <span class="badge bg-primary price-badge">
                        $<?= number_format($event['ticket_price'], 2) ?>
                    </span>
                    
                    <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                    <p class="card-text text-muted">
                        <i class="bi bi-calendar-event"></i>
                        <?= date('M j, Y', strtotime($event['date'])) ?>
                    </p>
                    <p class="card-text"><?= htmlspecialchars($event['location']) ?></p>
                    
                    <div class="mt-auto">
                        <a href="event_details.php?id=<?= $event['event_id'] ?>" 
                           class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update price display
        document.getElementById('priceFilter').addEventListener('input', function() {
            document.getElementById('priceValue').textContent = this.value;
        });
        
        // AJAX Live Search
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const query = this.value;
            
            searchTimeout = setTimeout(function() {
                if (query.length >= 2) {
                    fetch(`../api/search.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            // Handle search results
                            console.log(data);
                        });
                }
            }, 300);
        });
    </script>
</body>
</html>