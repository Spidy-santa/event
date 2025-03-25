<?php
/**
 * Common functions for the Event Management System
 */

// Generate CSRF token for form security
function generate_csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

// AI-Powered Recommendations - This function is also defined in events.php, but we include it here for consistency
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

// Get popular categories function - This function is also defined in events.php, but we include it here for consistency
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

// Format date for display
function format_event_date($date) {
  return date('F j, Y', strtotime($date));
}

// Format currency for display
function format_currency($amount) {
  return '₹' . number_format($amount, 2);
}

// Get user data by ID
function get_user_by_id($user_id) {
  global $pdo;
  try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
  } catch(PDOException $e) {
    error_log("User Query Error: " . $e->getMessage());
    return false;
  }
}

// Get event data by ID
function get_event_by_id($event_id) {
  global $pdo;
  try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    return $stmt->fetch();
  } catch(PDOException $e) {
    error_log("Event Query Error: " . $e->getMessage());
    return false;
  }
}

// Check if user is registered for an event
function is_user_registered($user_id, $event_id) {
  global $pdo;
  try {
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    return $stmt->rowCount() > 0;
  } catch(PDOException $e) {
    error_log("Registration Check Error: " . $e->getMessage());
    return false;
  }
}

// Sanitize input data
function sanitize_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

// Logger function
function log_activity($user_id, $action, $details = '') {
  global $pdo;
  try {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action, $details]);
    return true;
  } catch(PDOException $e) {
    error_log("Logging Error: " . $e->getMessage());
    return false;
  }
}
