<?php
session_start();
include 'includes/db.php';

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT e.*, o.name as organizer_name, o.contact_email, 
                        o.phone, o.created_at as organizer_since,
                        (e.total_tickets - COALESCE((SELECT SUM(ticket_qty) FROM registrations WHERE event_id = e.event_id AND status = 'confirmed'), 0)) as available_tickets
                      FROM events e 
                      LEFT JOIN organizers o ON e.organizer_id = o.organizer_id 
                      WHERE e.event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error'] = "Event not found!";
    header("Location: index.php");
    exit();
}

// Create organizer array if organizer exists
$organizer = null;
if (!empty($event['organizer_name'])) {
    $organizer = [
        'name' => $event['organizer_name'],
        'email' => $event['contact_email'],
        'phone' => $event['phone'],
        'member_since' => date('F Y', strtotime($event['organizer_since']))
    ];
}

// Set default values for undefined fields
$event['time'] = $event['time'] ?? '00:00:00';
$event['venue'] = $event['venue'] ?? 'To be announced';
$event['address'] = $event['address'] ?? 'Location details coming soon';
$event['available_tickets'] = $event['available_tickets'] ?? 0;
$event['ticket_price'] = $event['ticket_price'] ?? 0;
$event['capacity'] = $event['capacity'] ?? 'Unlimited';

// Parse the details JSON field
$details = json_decode($event['details'], true) ?? [];

// Fix image path if it's relative
if ($event['image_path'] && !filter_var($event['image_path'], FILTER_VALIDATE_URL)) {
    $event['image_path'] = str_replace('../', '', $event['image_path']);
}

// Fix additional images paths if they exist
if (!empty($details['additional_images']) && is_array($details['additional_images'])) {
    foreach ($details['additional_images'] as $key => $path) {
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            $details['additional_images'][$key] = str_replace('../', '', $path);
        }
    }
}

// Fix event description display
$event['description'] = nl2br(htmlspecialchars($event['description']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['title']) ?> | EventForge</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/custom.css">
  <link rel="stylesheet" href="assets/css/event-details.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
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
    }
    
    .navbar {
      background-color: var(--primary-color);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-brand span {
      color: var(--accent-color);
      font-weight: 700;
    }
    
    .nav-link i {
      margin-right: 5px;
    }
    
    .main-content {
      padding: 0 0 50px 0;
    }
    
    /* Event Hero Section */
    .event-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }
    
    .event-hero {
      position: relative;
      height: 500px;
      overflow: hidden;
      margin-bottom: 30px;
      border-radius: 10px;
      box-shadow: var(--card-shadow);
    }
    
    .event-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.9) 100%);
      z-index: 1;
    }
    
    .event-hero-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .event-hero:hover .event-hero-image {
      transform: scale(1.05);
    }
    
    .event-hero-content {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 30px;
      color: var(--white-color);
      z-index: 2;
    }
    
    .event-badge {
      display: inline-block;
      background-color: var(--primary-color);
      color: var(--white-color);
      padding: 5px 15px;
      border-radius: 30px;
      margin-bottom: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      animation: fadeInDown 0.5s;
    }
    
    .event-hero-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 15px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
      animation: fadeIn 0.7s;
    }
    
    .event-hero-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      animation: fadeInUp 0.8s;
    }
    
    .event-hero-meta-item {
      display: flex;
      align-items: center;
      margin-right: 20px;
      font-size: 0.9rem;
    }
    
    .event-hero-meta-item i {
      margin-right: 8px;
      color: var(--accent-color);
    }
    
    /* Event Meta Container */
    .event-meta-container {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 30px;
      margin-bottom: 40px;
    }
    
    @media (max-width: 992px) {
      .event-meta-container {
        grid-template-columns: 1fr;
      }
    }
    
    /* Info Cards */
    .event-info {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .info-card {
      background-color: var(--white-color);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      transition: var(--transition);
    }
    
    .info-card:hover {
      transform: translateY(-5px);
    }
    
    .info-card-header {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      background: linear-gradient(to right, var(--primary-color), #6f86ff);
      color: var(--white-color);
    }
    
    .info-card-icon {
      width: 40px;
      height: 40px;
      background-color: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 1.2rem;
    }
    
    .info-card-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 0;
    }
    
    .info-card-content {
      padding: 20px;
    }
    
    .info-card-content p {
      margin-bottom: 10px;
    }
    
    .info-card-content p:last-child {
      margin-bottom: 0;
    }
    
    /* Section Components */
    .section-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--light-color);
    }
    
    .section-header-icon {
      width: 45px;
      height: 45px;
      background-color: var(--primary-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: var(--white-color);
      font-size: 1.25rem;
    }
    
    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
      color: var(--dark-color);
    }
    
    .section-content {
      font-size: 1rem;
      line-height: 1.7;
      color: #4e5d78;
    }
    
    /* Event Description */
    .event-description {
      background-color: var(--white-color);
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: var(--card-shadow);
    }
    
    /* Organizer Section */
    .organizer-section {
      background-color: var(--white-color);
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: var(--card-shadow);
    }
    
    .organizer-profile {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .organizer-avatar {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      overflow: hidden;
      margin-right: 20px;
      border: 3px solid var(--primary-color);
    }
    
    .organizer-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .organizer-info h3 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark-color);
    }
    
    .organizer-info p {
      font-size: 0.875rem;
      color: #6c757d;
      margin-bottom: 5px;
    }
    
    .contact-info {
      background-color: var(--light-color);
      border-radius: 8px;
      padding: 15px;
    }
    
    .contact-info p {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }
    
    .contact-info p i {
      width: 30px;
      color: var(--primary-color);
    }
    
    /* Gallery Section */
    .event-gallery-section {
      margin-bottom: 40px;
      padding: 25px;
      background-color: var(--white-color);
      border-radius: 10px;
      box-shadow: var(--card-shadow);
    }
    
    .swiper-container {
      margin-top: 20px;
      overflow: hidden;
      border-radius: 8px;
    }
    
    .gallery-slide {
      aspect-ratio: 16/9;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;
    }
    
    .gallery-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .gallery-slide:hover img {
      transform: scale(1.05);
    }
    
    /* Lightbox */
    .lightbox {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
    
    .lightbox-content {
      position: relative;
      max-width: 90vw;
      max-height: 90vh;
    }
    
    .lightbox-image {
      max-width: 100%;
      max-height: 90vh;
      border-radius: 8px;
    }
    
    .lightbox-close {
      position: absolute;
      top: -40px;
      right: 0;
      color: var(--white-color);
      font-size: 24px;
      cursor: pointer;
    }
    
    .lightbox-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 50px;
      height: 50px;
      background-color: rgba(255, 255, 255, 0.2);
      color: var(--white-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .lightbox-nav:hover {
      background-color: rgba(255, 255, 255, 0.4);
    }
    
    .lightbox-prev {
      left: -70px;
    }
    
    .lightbox-next {
      right: -70px;
    }
    
    /* Buttons */
    .btn {
      padding: 10px 20px;
      font-weight: 600;
      transition: var(--transition);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
      background-color: #3c5fd7;
      border-color: #3c5fd7;
      transform: translateY(-2px);
    }
    
    /* Animations */
    .animated {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    
    .animated.animate {
      opacity: 1;
      transform: translateY(0);
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .event-hero {
        height: 400px;
      }
      
      .event-hero-title {
        font-size: 2rem;
      }
      
      .event-hero-meta {
        flex-direction: column;
        gap: 10px;
      }
      
      .event-hero-meta-item {
        margin-right: 0;
      }
      
      .event-meta-container {
        grid-template-columns: 1fr;
      }
      
      .lightbox-nav {
        width: 40px;
        height: 40px;
      }
      
      .lightbox-prev {
        left: -50px;
      }
      
      .lightbox-next {
        right: -50px;
      }
    }
    
    @media (max-width: 480px) {
      .event-hero {
        height: 350px;
      }
      
      .event-hero-title {
        font-size: 1.75rem;
      }
      
      .event-badge {
        font-size: 0.75rem;
      }
      
      .section-header-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
      }
      
      .section-title {
        font-size: 1.25rem;
      }
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

  <div class="main-content">
    <div class="event-container">
      <!-- Event Hero Section -->
      <div class="event-hero animated">
        <img src="<?= htmlspecialchars($event['image_path'] ?? 'assets/images/events/default.jpg') ?>" 
                     class="event-hero-image" 
                     alt="<?= htmlspecialchars($event['title']) ?>"
                     loading="eager"
                     fetchpriority="high">
        <div class="event-hero-content">
          <span class="event-badge"><i class="fas fa-tag"></i> <?= ucfirst($event['category']) ?></span>
          <h1 class="event-hero-title"><?= htmlspecialchars($event['title']) ?></h1>
          <div class="event-hero-meta">
            <div class="event-hero-meta-item">
              <i class="fas fa-calendar-day"></i> <?= date('F j, Y', strtotime($event['date'])) ?>
            </div>
            <div class="event-hero-meta-item">
              <i class="fas fa-clock"></i> <?= date('g:i A', strtotime($event['time'])) ?>
            </div>
            <div class="event-hero-meta-item">
              <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?>
            </div>
            <div class="event-hero-meta-item">
              <i class="fas fa-ticket-alt"></i> $<?= number_format($event['ticket_price'], 2) ?>
            </div>
          </div>
        </div>
      </div>

      <div class="event-meta-container">
        <!-- Event Details -->
        <div class="event-info">
          <div class="info-card">
            <div class="info-card-header">
              <div class="info-card-icon">
                <i class="fas fa-calendar-alt"></i>
              </div>
              <h3 class="info-card-title">Date & Time</h3>
            </div>
            <div class="info-card-content">
              <p><i class="fas fa-calendar-day me-2"></i> <?= date('F j, Y', strtotime($event['date'])) ?></p>
              <p><i class="fas fa-clock me-2"></i> <?= date('g:i A', strtotime($event['time'])) ?></p>
            </div>
          </div>

          <div class="info-card">
            <div class="info-card-header">
              <div class="info-card-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <h3 class="info-card-title">Location</h3>
            </div>
            <div class="info-card-content">
              <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
              <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
              <p><strong>Address:</strong> <?= htmlspecialchars($event['address']) ?></p>
              <?php if (!empty($details['directions'])): ?>
                <p><strong>Directions:</strong> <?= htmlspecialchars($details['directions']) ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="info-card">
            <div class="info-card-header">
              <div class="info-card-icon">
                <i class="fas fa-ticket-alt"></i>
              </div>
              <h3 class="info-card-title">Tickets</h3>
            </div>
            <div class="info-card-content">
              <p><strong>Price:</strong> $<?= number_format($event['ticket_price'], 2) ?></p>
              <p><strong>Available:</strong> <?= $event['available_tickets'] ?> tickets</p>
              <p><strong>Capacity:</strong> <?= $event['capacity'] ?></p>
              <?php if (!empty($event['reg_start_date']) && !empty($event['reg_end_date'])): ?>
                <p><strong>Registration Period:</strong> <?= date('M j, Y', strtotime($event['reg_start_date'])) ?> - <?= date('M j, Y', strtotime($event['reg_end_date'])) ?></p>
              <?php endif; ?>
              <?php if (isset($_SESSION['user_id']) && $event['available_tickets'] > 0): ?>
                <a href="book_tickets.php?event_id=<?= $event_id ?>" class="btn btn-primary mt-2">Book Tickets</a>
              <?php elseif ($event['available_tickets'] <= 0): ?>
                <p class="text-danger mt-2"><strong>Sold Out!</strong></p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if (!empty($details)): ?>
        <div class="event-details-section">
          <div class="section-header">
            <div class="section-header-icon">
              <i class="fas fa-list-alt"></i>
            </div>
            <h2 class="section-header-title">Additional Details</h2>
          </div>
          <div class="details-content">
            <div class="row">
              <?php foreach($details as $key => $value): ?>
                <?php if (!empty($value) && $key != 'gallery' && $key != 'additional_images'): ?>
                <div class="col-md-6 mb-3">
                  <div class="detail-item">
                    <h4><?= ucwords(str_replace('_', ' ', $key)) ?></h4>
                    <p><?= is_array($value) ? implode(', ', $value) : htmlspecialchars($value) ?></p>
                  </div>
                </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($organizer)): ?>
        <div class="organizer-section">
          <div class="section-header">
            <div class="section-header-icon">
              <i class="fas fa-user-tie"></i>
            </div>
            <h2 class="section-header-title">Event Organizer</h2>
          </div>
          <div class="organizer-card">
            <div class="organizer-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div class="organizer-info">
              <h3 class="organizer-name"><?= htmlspecialchars($organizer['name']) ?></h3>
              <?php if (!empty($organizer['email'])): ?>
                <p class="organizer-detail">
                  <i class="fas fa-envelope"></i>
                  <span><?= htmlspecialchars($organizer['email']) ?></span>
                </p>
              <?php endif; ?>
              <?php if (!empty($organizer['phone'])): ?>
                <p class="organizer-detail">
                  <i class="fas fa-phone"></i>
                  <span><?= htmlspecialchars($organizer['phone']) ?></span>
                </p>
              <?php endif; ?>
              <?php if (!empty($organizer['address'])): ?>
                <p class="organizer-detail">
                  <i class="fas fa-location-dot"></i>
                  <span><?= htmlspecialchars($organizer['address']) ?></span>
                </p>
              <?php endif; ?>
              <!-- Add member since info -->
              <p class="organizer-detail">
                <i class="fas fa-clock"></i>
                <span>Member since <?= htmlspecialchars($organizer['member_since']) ?></span>
              </p>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Modified Gallery Section -->
      <?php if (!empty($details['additional_images'])): ?>
      <div class="event-gallery-section animated">
        <div class="gallery-header">
          <div class="gallery-header-icon">
            <i class="fas fa-images"></i>
          </div>
          <h2 class="gallery-header-title">Event Gallery</h2>
        </div>
        
        <div class="swiper event-gallery-swiper">
          <div class="swiper-wrapper">
            <?php foreach ($details['additional_images'] as $index => $image_path): ?>
            <div class="swiper-slide">
              <div class="gallery-card">
                <img src="<?= $image_path ?>" 
                     alt="Event Image <?= $index + 1 ?>" 
                     loading="lazy"
                     class="gallery-image">
                <div class="gallery-card-overlay">
                  <button class="gallery-view-btn" aria-label="View Image">
                    <i class="fas fa-expand-alt"></i>
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="swiper-pagination"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
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
        <p class="mb-0 text-center">&copy; <span id="current-year">2025</span> EventForge. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
  <script src="assets/js/custom.js"></script>
  <script>
    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
      // Add animation classes
      const animatedElements = document.querySelectorAll('.animated');
      
      // Create an observer
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('fadeInUp');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.1
      });
      
      // Observe each element
      animatedElements.forEach(element => {
        observer.observe(element);
      });
      
      // Initialize Swiper with improved settings
      if (document.querySelector('.event-gallery-swiper')) {
        const swiper = new Swiper('.event-gallery-swiper', {
          slidesPerView: 1,
          spaceBetween: 20,
          loop: true,
          grabCursor: true,
          autoplay: {
            delay: 3000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true
          },
          effect: 'slide',
          speed: 800,
          pagination: {
            el: '.swiper-pagination',
            clickable: true,
            dynamicBullets: true
          },
          navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
          },
          breakpoints: {
            640: {
              slidesPerView: 2,
            },
            992: {
              slidesPerView: 3,
            }
          },
          on: {
            init: function() {
              // Add animation class after initialization
              document.querySelector('.event-gallery-section').classList.add('fadeIn');
            }
          }
        });
      }
      
      // Gallery lightbox functionality
      const gallerySlides = document.querySelectorAll('[data-gallery-item] img');
      if (gallerySlides.length > 0) {
        // Create lightbox elements
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
          <div class="lightbox-content">
            <img src="" alt="Event Image">
            <div class="lightbox-counter">1 / ${gallerySlides.length}</div>
          </div>
          <div class="lightbox-close">&times;</div>
          <div class="lightbox-nav lightbox-prev"><i class="fas fa-chevron-left"></i></div>
          <div class="lightbox-nav lightbox-next"><i class="fas fa-chevron-right"></i></div>
        `;
        document.body.appendChild(lightbox);
        
        const lightboxImg = lightbox.querySelector('img');
        const lightboxClose = lightbox.querySelector('.lightbox-close');
        const lightboxPrev = lightbox.querySelector('.lightbox-prev');
        const lightboxNext = lightbox.querySelector('.lightbox-next');
        const lightboxCounter = lightbox.querySelector('.lightbox-counter');
        
        let currentIndex = 0;
        
        // Update counter
        function updateCounter() {
          lightboxCounter.textContent = `${currentIndex + 1} / ${gallerySlides.length}`;
        }
        
        // Open lightbox when clicking on a gallery image
        gallerySlides.forEach((img, index) => {
          img.addEventListener('click', function() {
            currentIndex = index;
            lightboxImg.src = this.src;
            updateCounter();
            lightbox.classList.add('show');
          });
        });
        
        // Close lightbox
        lightboxClose.addEventListener('click', function() {
          lightbox.classList.remove('show');
        });
        
        // Navigate to previous image
        lightboxPrev.addEventListener('click', function() {
          currentIndex = (currentIndex - 1 + gallerySlides.length) % gallerySlides.length;
          lightboxImg.src = gallerySlides[currentIndex].src;
          updateCounter();
        });
        
        // Navigate to next image
        lightboxNext.addEventListener('click', function() {
          currentIndex = (currentIndex + 1) % gallerySlides.length;
          lightboxImg.src = gallerySlides[currentIndex].src;
          updateCounter();
        });
        
        // Close lightbox when clicking outside the image
        lightbox.addEventListener('click', function(e) {
          if (e.target === lightbox) {
            lightbox.classList.remove('show');
          }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
          if (!lightbox.classList.contains('show')) return;
          
          if (e.key === 'Escape') {
            lightbox.classList.remove('show');
          } else if (e.key === 'ArrowLeft') {
            currentIndex = (currentIndex - 1 + gallerySlides.length) % gallerySlides.length;
            lightboxImg.src = gallerySlides[currentIndex].src;
            updateCounter();
          } else if (e.key === 'ArrowRight') {
            currentIndex = (currentIndex + 1) % gallerySlides.length;
            lightboxImg.src = gallerySlides[currentIndex].src;
            updateCounter();
          }
        });
      }
      
      // Back to top button functionality
      const backToTopButton = document.createElement('a');
      backToTopButton.href = '#';
      backToTopButton.className = 'back-to-top';
      backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
      document.body.appendChild(backToTopButton);
      
      // Show/hide back to top button based on scroll position
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.add('show');
        } else {
          backToTopButton.classList.remove('show');
        }
      });
      
      // Smooth scroll to top when clicked
      backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
      
      // Set current year in footer
      document.getElementById('current-year').textContent = new Date().getFullYear();
    });
  </script>
</body>
</html>