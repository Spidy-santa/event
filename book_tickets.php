<?php
session_start();
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to book tickets.";
    header("Location: login.php");
    exit();
}

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT) : 0;

// Initialize variables
$event = null;
$error = null;
$success = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tickets'])) {
    $ticket_qty = filter_input(INPUT_POST, 'ticket_qty', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    // Validate ticket quantity
    if ($ticket_qty <= 0) {
        $error = "Please select at least 1 ticket.";
    } else {
        try {
            // Check available tickets
            $stmt = $pdo->prepare("SELECT e.*, 
                                (e.total_tickets - COALESCE((SELECT SUM(ticket_qty) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed'), 0)) as available_tickets 
                                FROM events e WHERE e.event_id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if ($event && $ticket_qty <= $event['available_tickets']) {
                // Begin transaction
                $pdo->beginTransaction();
                
                // Insert booking
                $stmt = $pdo->prepare("INSERT INTO registrations 
                                      (user_id, event_id, ticket_qty, status, booking_date) 
                                      VALUES (?, ?, ?, 'pending', NOW())");
                $stmt->execute([$user_id, $event_id, $ticket_qty]);
                
                // Get the new registration ID
                $registration_id = $pdo->lastInsertId();
                
                // Commit transaction
                $pdo->commit();
                
                // Set success message
                $success = "Your booking has been confirmed! Booking ID: #" . $registration_id;
                
                // Redirect to dashboard after short delay
                header("Refresh: 3; URL=dashboard.php");
            } else {
                $error = "Sorry, there are not enough tickets available.";
            }
        } catch (PDOException $e) {
            // Roll back transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get event details if not already fetched
if (!$event && $event_id) {
    try {
        $stmt = $pdo->prepare("SELECT e.*, 
                              (e.total_tickets - COALESCE((SELECT SUM(ticket_qty) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed'), 0)) as available_tickets 
                              FROM events e WHERE e.event_id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            $error = "Event not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets | EventForge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --white-color: #ffffff;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f8fa;
            color: #2e3a59;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand span {
            color: var(--accent-color);
            font-weight: 700;
        }
        
        .content-wrapper {
            flex: 1;
            padding: 50px 0;
        }
        
        .booking-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .booking-card {
            background-color: var(--white-color);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .booking-header {
            background: linear-gradient(to right, var(--primary-color), #6f86ff);
            color: white;
            padding: 20px;
        }
        
        .booking-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .booking-body {
            padding: 30px;
        }
        
        .event-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .event-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .event-details {
            flex: 1;
        }
        
        .event-details h3 {
            margin-top: 0;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .event-meta {
            margin-top: 15px;
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .event-meta-item i {
            width: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .success-message {
            background-color: rgba(28, 200, 138, 0.1);
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: rgba(231, 74, 59, 0.1);
            border-left: 4px solid var(--danger-color);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .booking-form label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .price-summary {
            background-color: var(--light-color);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            border-top: 1px solid #e3e6f0;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            padding: 10px 25px;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: #3c5fd7;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Event<span>Forge</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="container booking-container">
            <div class="booking-card">
                <div class="booking-header">
                    <h2><i class="fas fa-ticket-alt me-2"></i> Book Tickets</h2>
                </div>
                <div class="booking-body">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($event && !$success): ?>
                        <div class="event-info">
                            <div class="event-image">
                                <img src="<?= htmlspecialchars($event['image_path'] ?? 'assets/images/events/default.jpg') ?>" 
                                     alt="<?= htmlspecialchars($event['title']) ?>">
                            </div>
                            <div class="event-details">
                                <h3><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="fas fa-calendar-day"></i>
                                        <span><?= date('F j, Y', strtotime($event['date'])) ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($event['location']) ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span><strong><?= $event['available_tickets'] ?></strong> tickets available</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>₹<?= number_format($event['ticket_price'], 2) ?> per ticket</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="booking-form">
                            <div class="mb-4">
                                <label for="ticket_qty" class="form-label">Number of Tickets</label>
                                <input type="number" class="form-control" id="ticket_qty" name="ticket_qty" min="1" max="<?= $event['available_tickets'] ?>" value="1" required>
                                <div class="form-text">Maximum <?= $event['available_tickets'] ?> tickets per booking.</div>
                            </div>
                            
                            <div class="price-summary">
                                <h4>Price Summary</h4>
                                <div class="price-row">
                                    <span>Ticket Price:</span>
                                    <span>₹<?= number_format($event['ticket_price'], 2) ?> x <span id="ticket-count">1</span></span>
                                </div>
                                
                                <div class="price-row total">
                                    <span>Total:</span>
                                    <span>₹<span id="total-price"><?= number_format($event['ticket_price'], 2) ?></span></span>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <a href="event_details.php?id=<?= $event_id ?>" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Event
                                </a>
                                <button type="submit" name="book_tickets" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i> Confirm Booking
                                </button>
                            </div>
                        </form>
                    <?php elseif (!$success): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Event Not Found</h3>
                            <p>The event you are looking for does not exist or has been removed.</p>
                            <a href="events.php" class="btn btn-primary mt-3">
                                <i class="fas fa-calendar-alt me-2"></i> Browse Events
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>EventForge</h5>
                    <p class="mb-3">Your one-stop solution for event management.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="events.php" class="text-white">Events</a></li>
                        <li><a href="dashboard.php" class="text-white">Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@eventforge.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> EventForge. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate total price based on ticket quantity
        document.addEventListener('DOMContentLoaded', function() {
            const ticketQtyInput = document.getElementById('ticket_qty');
            const ticketCountEl = document.getElementById('ticket-count');
            const totalPriceEl = document.getElementById('total-price');
            
            if (ticketQtyInput && ticketCountEl && totalPriceEl) {
                const ticketPrice = <?= $event ? $event['ticket_price'] : 0 ?>;
                
                ticketQtyInput.addEventListener('input', function() {
                    const quantity = parseInt(this.value) || 0;
                    const total = quantity * ticketPrice;
                    
                    ticketCountEl.textContent = quantity;
                    totalPriceEl.textContent = total.toFixed(2);
                });
            }
        });
    </script>
</body>
</html>
