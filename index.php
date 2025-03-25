<?php
session_start();
require_once 'includes/db.php';

// Fetch trending events (based on registration count)
$stmt = $pdo->prepare("
    SELECT e.*, u.name as organizer_name, COUNT(r.reg_id) as registration_count 
    FROM events e 
    LEFT JOIN users u ON e.organizer_id = u.user_id 
    LEFT JOIN registrations r ON e.event_id = r.event_id 
    WHERE e.date >= CURDATE()
    GROUP BY e.event_id, e.title, e.date, e.location, e.category, e.ticket_price, e.image_path, u.name
    ORDER BY registration_count DESC 
    LIMIT 3
");
$stmt->execute();
$trending_events = $stmt->fetchAll();

// Fetch trending events based on user preferences (if logged in)
$trending_events = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as organizer_name, COUNT(r.reg_id) as registration_count 
        FROM events e 
        LEFT JOIN users u ON e.organizer_id = u.user_id 
        LEFT JOIN registrations r ON e.event_id = r.event_id 
        WHERE e.date >= CURDATE()
        AND e.category IN (
            SELECT category FROM user_preferences WHERE user_id = ?
        )
        GROUP BY e.event_id
        ORDER BY registration_count DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $trending_events = $stmt->fetchAll();
}

// If no user-specific trending events, show general trending
if (empty($trending_events)) {
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as organizer_name, COUNT(r.reg_id) as registration_count 
        FROM events e 
        LEFT JOIN users u ON e.organizer_id = u.user_id 
        LEFT JOIN registrations r ON e.event_id = r.event_id 
        WHERE e.date >= CURDATE()
        GROUP BY e.event_id
        ORDER BY registration_count DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $trending_events = $stmt->fetchAll();
}

// Fetch top 5 events (based on registration count)
$stmt = $pdo->prepare("
    SELECT e.*, u.name as organizer_name, COUNT(r.reg_id) as registration_count 
    FROM events e 
    LEFT JOIN users u ON e.organizer_id = u.user_id 
    LEFT JOIN registrations r ON e.event_id = r.event_id 
    WHERE e.date >= CURDATE()
    GROUP BY e.event_id
    ORDER BY registration_count DESC 
    LIMIT 5
");
$stmt->execute();
$top_events = $stmt->fetchAll();

// Get the featured event (the most popular one)
$featured_event = !empty($top_events) ? $top_events[0] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>EventForge - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">EventForge</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php"><i class="fas fa-user-shield"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="organizer/login.php"><i class="fas fa-calendar-alt"></i> Organizer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendee/login.php"><i class="fas fa-user"></i> User</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        <?php else: ?>
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section" style="height: 600px; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/liveconcert.jfif'); background-size: cover; background-position: center; color: white; padding: 200px 0; margin-bottom: 2rem; border-top: 5px solid #8b4513; border-bottom: 5px solid #8b4513;">
        <div class="container text-center">
            <h1 style="font-size: 4.2rem; font-weight: bold; color: #ffd700; text-shadow: 2px 2px 4px rgba(0,0,0,0.7);">Discover Exciting Events Near You</h1>
            <p class="lead" style="font-size: 2rem; color: #f5f0e5; text-shadow: 1px 1px 3px rgba(0,0,0,0.7);">Explore, register, and manage your events with ease</p>
            <a href="events.php" class="btn btn-primary btn-lg mt-3" style="border: 2px solid #ffd700; padding: 10px 30px;">
                <i class="fas fa-search"></i> Browse All Events
            </a>
        </div>
    </div>

    <!-- Featured Event Section (if available) -->
    <?php if ($featured_event): 
        $image_path = $featured_event['image_path'] ?? 'assets/images/events/default.jpg';
        if (!filter_var($image_path, FILTER_VALIDATE_URL)) {
            $image_path = str_replace('../', '', $image_path);
        }
    ?>
    <div class="container mt-4">
        <div class="featured-event">
            <img src="<?= $image_path ?>" class="featured-event-img" alt="<?= htmlspecialchars($featured_event['title']) ?>">
            <div class="featured-badge">
                <i class="fas fa-star"></i> Featured
            </div>
            <div class="featured-event-content">
                <h2 class="featured-event-title"><?= htmlspecialchars($featured_event['title']) ?></h2>
                <div class="row">
                    <div class="col-md-8">
                        <p>
                            <i class="fas fa-calendar-day"></i> <?= date('F j, Y', strtotime($featured_event['date'])) ?> &nbsp;|&nbsp;
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($featured_event['location']) ?> &nbsp;|&nbsp;
                            <i class="fas fa-users"></i> <?= $featured_event['registration_count'] ?> registered
                        </p>
                        <a href="event_details.php?id=<?= $featured_event['event_id'] ?>" class="btn btn-primary mt-2">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-primary mb-2"><?= ucfirst($featured_event['category']) ?></span>
                        <h4 class="text-white">$<?= number_format($featured_event['ticket_price'], 2) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Latest Events Section -->
    <div class="container mt-5">
        <div class="section-divider"></div>
        <h2 class="events-header"><i class="fas fa-sparkles"></i> Latest Events</h2>
        <div class="row">
            <?php
            // Fetch latest events with total tickets
            $stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name, 
                                  (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registered_count,
                                  e.total_tickets as total_tickets
                                  FROM events e 
                                  JOIN users u ON e.organizer_id = u.user_id 
                                  ORDER BY e.event_id DESC 
                                  LIMIT 5");
            $stmt->execute();
            $latest_events = $stmt->fetchAll();

            foreach ($latest_events as $event): 
                $image_path = $event['image_path'] ?? 'assets/images/events/default.jpg';
                if (!filter_var($image_path, FILTER_VALIDATE_URL)) {
                    $image_path = str_replace('../', '', $image_path);
                }
                // Calculate remaining tickets
                $remaining_tickets = $event['total_tickets'] - ($event['registered_count'] ?? 0);
            ?>
            <div class="col-md-4 mb-4">
                <div class="card event-card h-100 animated">
                    <div class="ribbon ribbon-top-right"><span>New</span></div>
                    <img src="<?= $image_path ?>" class="event-image" alt="<?= htmlspecialchars($event['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                        <p class="card-text">
                            <i class="fas fa-calendar-day"></i> <?= date('F j, Y', strtotime($event['date'])) ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?><br>
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($event['organizer_name']) ?><br>
                            <i class="fas fa-ticket-alt"></i> <?= $remaining_tickets ?> tickets left 
                            <small class="text-muted">(Total: <?= $event['total_tickets'] ?>)</small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= ucfirst($event['category']) ?></span>
                            <span class="text-muted" style="font-weight: bold; color: var(--primary-color) !important;">$<?= number_format($event['ticket_price'], 2) ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="event_details.php?id=<?= $event['event_id'] ?>" class="btn btn-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($latest_events)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No new events available at the moment.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h4>About EventForge</h4>
                    <p>EventForge is your premier destination for discovering, organizing, and managing events of all types.</p>
                    <div class="social-icons mt-2">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h4>Contact Us</h4>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> 123 Event Street, Celebration City</p>
                        <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                        <p><i class="fas fa-envelope"></i> info@eventforge.com</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h4>Quick Links</h4>
                    <div class="footer-links">
                        <a href="events.php"><i class="fas fa-calendar-alt"></i> Browse Events</a>
                        <a href="organizer/login.php"><i class="fas fa-user-tie"></i> Organizer Portal</a>
                        <a href="attendee/login.php"><i class="fas fa-user"></i> User Login</a>
                        <a href="#"><i class="fas fa-question-circle"></i> Help & Support</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright mt-3">
            <div class="container">
                <p class="mb-0 text-center"> 2025 EventForge. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>