<?php
session_start();
require '../includes/db.php';

// Restrict to attendees
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'attendee') {
  header("Location: ../login.php");
  exit();
}

// Fetch attendee data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user's event requests
$query = "SELECT * FROM event_requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$event_requests = $stmt->get_result();

// Fetch upcoming events
$stmt = $pdo->prepare("SELECT events.* FROM registrations 
                      JOIN events ON registrations.event_id = events.event_id
                      WHERE registrations.user_id = ? AND events.date >= CURDATE()
                      ORDER BY events.date ASC LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_events = $stmt->fetchAll();

// Fetch event recommendations
$stmt = $pdo->prepare("SELECT * FROM events 
                      WHERE category IN (
                        SELECT category FROM registrations
                        JOIN events ON registrations.event_id = events.event_id
                        WHERE user_id = ?
                      ) AND date >= CURDATE()
                      ORDER BY RAND() LIMIT 3");
$stmt->execute([$user_id]);
$recommended_events = $stmt->fetchAll();

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications 
                      WHERE user_id = ? AND is_read = 0
                      ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Handle request responses if user is responding to admin 
if (isset($_POST['respond_to_quotation'])) {
  $request_id = $_POST['request_id'];
  $response = $_POST['response'];
  $counter_price = isset($_POST['counter_price']) ? $_POST['counter_price'] : 0;
  
  if ($response == 'accept') {
    $status = 'accepted';
  } elseif ($response == 'counter') {
    $status = 'counter_offer';
  } else {
    $status = 'rejected';
  }
  
  $updateQuery = "UPDATE event_requests SET 
                 status = ?, 
                 user_price_counter = ? 
                 WHERE id = ? AND user_id = ?";
                 
  $updateStmt = $conn->prepare($updateQuery);
  $updateStmt->bind_param("sdii", $status, $counter_price, $request_id, $user_id);
  
  if ($updateStmt->execute()) {
    $successMsg = "Your response has been submitted successfully.";
  } else {
    $errorMsg = "Error updating your response: " . $conn->error;
  }
  
  // Redirect to avoid form resubmission
  header("Location: dashboard.php?updated=1");
  exit();
}

// Handle responses to modified requests
if (isset($_POST['respond_to_modification'])) {
  $request_id = $_POST['request_id'];
  $response = $_POST['modification_response'];
  
  if ($response == 'accept') {
    $status = 'pending'; // Reset to pending for further processing
    $message = "Attendee accepted the modifications";
    
    // Insert entry into modification history
    $historyQuery = "INSERT INTO request_modifications 
                    (request_id, modified_by, modification_note, previous_status)
                    VALUES (?, ?, ?, ?)";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bind_param("iiss", $request_id, $user_id, $message, "modified");
    $historyStmt->execute();
    
  } else {
    $status = 'pending'; // Reset to pending but with rejection note
    $message = "Attendee rejected the modifications: " . $_POST['rejection_reason'];
    
    // Insert entry into modification history
    $historyQuery = "INSERT INTO request_modifications 
                    (request_id, modified_by, modification_note, previous_status)
                    VALUES (?, ?, ?, ?)";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bind_param("iiss", $request_id, $user_id, $message, "modified");
    $historyStmt->execute();
  }
  
  $updateQuery = "UPDATE event_requests SET 
                 status = ?, 
                 attendee_response = ?,
                 is_admin_modified = 0  
                 WHERE id = ? AND user_id = ?";
                 
  $updateStmt = $conn->prepare($updateQuery);
  $updateStmt->bind_param("ssii", $status, $message, $request_id, $user_id);
  
  if ($updateStmt->execute()) {
    $successMsg = "Your response to the modified request has been submitted.";
    
    // Create notification for admin
    $notifyQuery = "INSERT INTO notifications 
                   (user_id, title, message, created_at)
                   SELECT admin.user_id, 
                          'Event Request Response', 
                          ?, 
                          NOW()
                   FROM users admin
                   WHERE admin.role = 'admin'
                   LIMIT 1";
    
    $notifyStmt = $conn->prepare($notifyQuery);
    $notifyMsg = "User has responded to your modifications for event request #" . $request_id;
    $notifyStmt->bind_param("s", $notifyMsg);
    $notifyStmt->execute();
    
  } else {
    $errorMsg = "Error updating your response: " . $conn->error;
  }
  
  // Redirect to avoid form resubmission
  header("Location: dashboard.php?updated=1");
  exit();
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Attendee Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    .dashboard-card {
      transition: transform 0.2s;
    }
    .dashboard-card:hover {
      transform: translateY(-5px);
    }
    .notification-badge {
      position: absolute;
      top: -10px;
      right: -10px;
    }
    .request-card {
      margin-bottom: 20px;
      border-left: 5px solid #ccc;
    }
    .request-pending { border-left-color: #ffc107; }
    .request-approved { border-left-color: #28a745; }
    .request-rejected { border-left-color: #dc3545; }
    .request-modified { border-left-color: #0dcaf0; }
    .admin-comment {
      background-color: #f8f9fa;
      border-left: 3px solid #6c757d;
      padding: 10px 15px;
      margin-top: 15px;
    }
    .status-badge {
      font-size: 0.8rem;
      padding: 5px 10px;
    }
    .modification-highlight {
      background-color: #e8f4f8;
      border-left: 3px solid #0dcaf0;
      padding: 10px 15px;
      margin-top: 15px;
    }
    .modified-field {
      font-weight: bold;
      color: #0d6efd;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
      <div class="position-relative">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#notificationsModal">
          <i class="bi bi-bell"></i>
          <?php if(count($notifications) > 0): ?>
          <span class="badge bg-danger notification-badge"><?= count($notifications) ?></span>
          <?php endif; ?>
        </button>
        <a href="../logout.php" class="btn btn-outline-danger ms-2">Logout</a>
      </div>
    </div>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      Your response has been submitted successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row mb-4">
      <div class="col-md-3">
        <a href="events.php" class="card dashboard-card text-decoration-none">
          <div class="card-body text-center">
            <i class="bi bi-calendar-event fs-1 text-primary"></i>
            <h5 class="card-title">Browse Events</h5>
            <p class="card-text small">Find and register for upcoming events</p>
          </div>
        </a>
      </div>
      <div class="col-md-3">
        <a href="tickets.php" class="card dashboard-card text-decoration-none">
          <div class="card-body text-center">
            <i class="bi bi-ticket-perforated fs-1 text-success"></i>
            <h5 class="card-title">My Tickets</h5>
            <p class="card-text small">View and manage your event tickets</p>
          </div>
        </a>
      </div>
      <div class="col-md-3">
        <a href="request_event.php" class="card dashboard-card text-decoration-none">
          <div class="card-body text-center">
            <i class="bi bi-calendar-plus fs-1 text-warning"></i>
            <h5 class="card-title">Request Event</h5>
            <p class="card-text small">Request a personalized event</p>
          </div>
        </a>
      </div>
      <div class="col-md-3">
        <a href="profile.php" class="card dashboard-card text-decoration-none">
          <div class="card-body text-center">
            <i class="bi bi-person-circle fs-1 text-info"></i>
            <h5 class="card-title">My Profile</h5>
            <p class="card-text small">Update your personal information</p>
          </div>
        </a>
      </div>
    </div>

    <div class="row">
      <!-- Left Column: Event Requests -->
      <div class="col-lg-7">
        <div class="card mb-4 shadow-sm">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-check"></i> My Event Requests</h5>
            <a href="request_event.php" class="btn btn-sm btn-primary">Create New Request</a>
          </div>
          <div class="card-body">
            <?php if (mysqli_num_rows($event_requests) == 0): ?>
              <div class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="mt-3">You haven't made any event requests yet.</p>
                <a href="request_event.php" class="btn btn-primary">Request Your First Event</a>
              </div>
            <?php else: ?>
              <div class="accordion" id="requestAccordion">
                <?php $counter = 0; while ($request = mysqli_fetch_assoc($event_requests)): $counter++; 
                  $statusClass = '';
                  $statusBadge = '';
                  
                  if ($request['status'] == 'pending') {
                    $statusClass = 'request-pending';
                    $statusBadge = '<span class="badge bg-warning status-badge">Pending</span>';
                  } elseif ($request['status'] == 'approved' || $request['status'] == 'accepted') {
                    $statusClass = 'request-approved';
                    $statusBadge = '<span class="badge bg-success status-badge">Approved</span>';
                  } elseif ($request['status'] == 'rejected') {
                    $statusClass = 'request-rejected';
                    $statusBadge = '<span class="badge bg-danger status-badge">Rejected</span>';
                  } elseif ($request['status'] == 'quotation') {
                    $statusClass = 'request-pending';
                    $statusBadge = '<span class="badge bg-info status-badge">Quotation Available</span>';
                  } elseif ($request['status'] == 'modified') {
                    $statusClass = 'request-modified';
                    $statusBadge = '<span class="badge bg-info status-badge">Modified by Admin</span>';
                  } elseif ($request['status'] == 'counter_offer') {
                    $statusClass = 'request-pending';
                    $statusBadge = '<span class="badge bg-primary status-badge">Counter Offer Sent</span>';
                  }
                ?>
                  <div class="accordion-item request-card <?= $statusClass ?> mb-3 border">
                    <h2 class="accordion-header" id="heading<?= $counter ?>">
                      <button class="accordion-button <?= $counter > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $counter ?>">
                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                          <div>
                            <strong><?= htmlspecialchars(ucfirst($request['event_type'])) ?> Event</strong>
                            <small class="d-block text-muted">
                              <?= date('F j, Y', strtotime($request['preferred_date'])) ?> • 
                              <?= htmlspecialchars($request['location']) ?> • 
                              <?= htmlspecialchars($request['total_attendees']) ?> Attendees
                            </small>
                          </div>
                          <div class="ms-auto">
                            <?= $statusBadge ?>
                            <?php if (isset($request['is_admin_modified']) && $request['is_admin_modified'] == 1): ?>
                              <span class="badge bg-info ms-1">Modified</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </button>
                    </h2>
                    <div id="collapse<?= $counter ?>" class="accordion-collapse collapse <?= $counter == 1 ? 'show' : '' ?>" aria-labelledby="heading<?= $counter ?>">
                      <div class="accordion-body">
                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>Requested By:</strong> <?= htmlspecialchars($request['name']) ?></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($request['contact']) ?></p>
                            <p><strong>Event Type:</strong> <?= htmlspecialchars(ucfirst($request['event_type'])) ?></p>
                            <p><strong>Preferred Date:</strong> <?= date('F j, Y', strtotime($request['preferred_date'])) ?></p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>Location:</strong> <?= htmlspecialchars($request['location']) ?></p>
                            <p><strong>Attendees:</strong> <?= htmlspecialchars($request['total_attendees']) ?></p>
                            <p><strong>Budget:</strong> ₹<?= number_format($request['event_budget'] ?? 0, 2) ?></p>
                            <p><strong>Status:</strong> <?= $statusBadge ?></p>
                          </div>
                        </div>
                        
                        <div class="mt-3">
                          <h6>Event Details:</h6>
                          <p><?= nl2br(htmlspecialchars($request['event_details'])) ?></p>
                        </div>
                        
                        <?php if (isset($request['is_admin_modified']) && $request['is_admin_modified'] == 1): ?>
                        <div class="modification-highlight mt-3">
                          <h6><i class="bi bi-pencil-square"></i> Admin Modifications:</h6>
                          <div class="alert alert-info">
                            <p class="mb-2">
                              <i class="bi bi-info-circle"></i> The admin has suggested changes to your event request. Please review the modifications and respond.
                            </p>
                            <?php if (!empty($request['admin_comments'])): ?>
                            <p class="mb-0"><strong>Admin Comments:</strong> <?= nl2br(htmlspecialchars($request['admin_comments'])) ?></p>
                            <?php endif; ?>
                          </div>
                          
                          <div class="row">
                            <div class="col-md-12">
                              <div class="card mb-3">
                                <div class="card-header bg-light">
                                  <h6 class="mb-0">Modified Event Details</h6>
                                </div>
                                <div class="card-body">
                                  <div class="row">
                                    <div class="col-md-6">
                                      <p><strong>Event Type:</strong> <?= htmlspecialchars(ucfirst($request['event_type'])) ?></p>
                                      <p><strong>Preferred Date:</strong> <?= date('F j, Y', strtotime($request['preferred_date'])) ?></p>
                                      <p><strong>Location:</strong> <?= htmlspecialchars($request['location']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                      <p><strong>Attendees:</strong> <?= htmlspecialchars($request['total_attendees']) ?></p>
                                      <p><strong>Budget:</strong> ₹<?= number_format($request['event_budget'] ?? 0, 2) ?></p>
                                    </div>
                                  </div>
                                  <div class="mt-2">
                                    <p><strong>Event Details:</strong> <?= nl2br(htmlspecialchars($request['event_details'])) ?></p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          
                          <?php if ($request['status'] == 'modified'): ?>
                          <form method="POST" action="" class="mt-3">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <div class="mb-3">
                              <label class="form-label fw-bold">Your Response to Modifications:</label>
                              <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modification_response" id="accept_mod<?= $counter ?>" value="accept" required>
                                <label class="form-check-label" for="accept_mod<?= $counter ?>">
                                  <span class="text-success"><i class="bi bi-check-circle"></i> Accept Modifications</span>
                                </label>
                              </div>
                              <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modification_response" id="reject_mod<?= $counter ?>" value="reject" required>
                                <label class="form-check-label" for="reject_mod<?= $counter ?>">
                                  <span class="text-danger"><i class="bi bi-x-circle"></i> Reject Modifications</span>
                                </label>
                              </div>
                            </div>
                            
                            <div class="mb-3 rejection-reason" style="display: none;">
                              <label for="rejection_reason<?= $counter ?>" class="form-label">Reason for rejection:</label>
                              <textarea name="rejection_reason" id="rejection_reason<?= $counter ?>" class="form-control" rows="3" placeholder="Please explain why you're rejecting the modifications..."></textarea>
                            </div>
                            
                            <button type="submit" name="respond_to_modification" class="btn btn-primary">Submit Response</button>
                          </form>
                          
                          <script>
                            document.addEventListener('DOMContentLoaded', function() {
                              const rejectRadio<?= $counter ?> = document.getElementById('reject_mod<?= $counter ?>');
                              const acceptRadio<?= $counter ?> = document.getElementById('accept_mod<?= $counter ?>');
                              const rejectionDiv<?= $counter ?> = document.querySelector('#collapse<?= $counter ?> .rejection-reason');
                              
                              rejectRadio<?= $counter ?>.addEventListener('change', function() {
                                if(this.checked) {
                                  rejectionDiv<?= $counter ?>.style.display = 'block';
                                }
                              });
                              
                              acceptRadio<?= $counter ?>.addEventListener('change', function() {
                                if(this.checked) {
                                  rejectionDiv<?= $counter ?>.style.display = 'none';
                                }
                              });
                            });
                          </script>
                          <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($request['admin_comments']) && (!isset($request['is_admin_modified']) || $request['is_admin_modified'] != 1)): ?>
                        <div class="admin-comment mt-3">
                          <h6><i class="bi bi-chat-left-text"></i> Admin Response:</h6>
                          <p><?= nl2br(htmlspecialchars($request['admin_comments'])) ?></p>
                          
                          <?php if ($request['status'] == 'quotation'): ?>
                          <div class="bg-light p-3 mt-3 border">
                            <h6 class="text-primary">Price Quote: ₹<?= number_format($request['price_quote'] ?? 0, 2) ?></h6>
                            <p class="small">Please respond to this quotation:</p>
                            
                            <form method="POST" action="">
                              <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                              <div class="mb-3">
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="response" id="accept<?= $counter ?>" value="accept" required>
                                  <label class="form-check-label" for="accept<?= $counter ?>">Accept Quotation</label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="response" id="counter<?= $counter ?>" value="counter">
                                  <label class="form-check-label" for="counter<?= $counter ?>">Counter Offer</label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="response" id="reject<?= $counter ?>" value="reject">
                                  <label class="form-check-label" for="reject<?= $counter ?>">Reject</label>
                                </div>
                              </div>
                              
                              <div class="mb-3 counter-offer-container d-none">
                                <label for="counter_price<?= $counter ?>" class="form-label">Your Counter Offer (₹):</label>
                                <input type="number" class="form-control" id="counter_price<?= $counter ?>" name="counter_price" min="0" step="0.01">
                              </div>
                              
                              <button type="submit" name="respond_to_quotation" class="btn btn-primary">Submit Response</button>
                            </form>
                          </div>
                          <?php endif; ?>
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Upcoming Events -->
        <div class="card mb-4 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Upcoming Events</h5>
          </div>
          <div class="card-body">
            <?php if(empty($upcoming_events)): ?>
            <p class="text-muted">No upcoming events. <a href="events.php">Browse events</a> to get started!</p>
            <?php else: ?>
            <div class="list-group">
              <?php foreach($upcoming_events as $event): ?>
              <a href="event_details.php?id=<?= $event['event_id'] ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                  <div>
                    <h6><?= htmlspecialchars($event['title']) ?></h6>
                    <small class="text-muted"><?= date('M j, Y', strtotime($event['date'])) ?></small>
                  </div>
                  <div>
                    <span class="badge bg-primary"><?= $event['category'] ?></span>
                  </div>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Right Column: Recommendations and Stats -->
      <div class="col-lg-5">
        <!-- Recommended Events -->
        <div class="card mb-4 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-star"></i> Recommended for You</h5>
          </div>
          <div class="card-body">
            <?php if(empty($recommended_events)): ?>
            <p class="text-muted">No recommendations yet. Start exploring events!</p>
            <?php else: ?>
            <div class="row g-3">
              <?php foreach($recommended_events as $event): ?>
              <div class="col-md-6">
                <div class="card dashboard-card h-100">
                  <img src="<?= $event['image_url'] ?? '../assets/images/event-placeholder.jpg' ?>" class="card-img-top" alt="<?= $event['title'] ?>">
                  <div class="card-body">
                    <h6 class="card-title"><?= htmlspecialchars($event['title']) ?></h6>
                    <p class="card-text small"><?= date('M j, Y', strtotime($event['date'])) ?></p>
                    <a href="event_details.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-outline-primary">
                      View Details
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Quick Statistics -->
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Your Stats</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col">
                <div class="border-end">
                  <h3 class="text-primary"><?= mysqli_num_rows($event_requests) ?></h3>
                  <p class="small text-muted mb-0">Event Requests</p>
                </div>
              </div>
              <div class="col">
                <div class="border-end">
                  <h3 class="text-success"><?= count($upcoming_events) ?></h3>
                  <p class="small text-muted mb-0">Upcoming Events</p>
                </div>
              </div>
              <div class="col">
                <div>
                  <h3 class="text-info"><?= count($notifications) ?></h3>
                  <p class="small text-muted mb-0">Notifications</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notifications Modal -->
  <div class="modal fade" id="notificationsModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Notifications</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if(empty($notifications)): ?>
          <p class="text-muted">No new notifications.</p>
          <?php else: ?>
          <div class="list-group">
            <?php foreach($notifications as $notification): ?>
            <div class="list-group-item">
              <h6><?= htmlspecialchars($notification['title']) ?></h6>
              <p><?= htmlspecialchars($notification['message']) ?></p>
              <small class="text-muted"><?= date('M j, Y h:i A', strtotime($notification['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Show/hide counter offer field based on selection
    document.addEventListener('DOMContentLoaded', function() {
      // Handle quotation response
      const radioButtons = document.querySelectorAll('input[name="response"]');
      radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
          const form = this.closest('form');
          const counterOfferContainer = form.querySelector('.counter-offer-container');
          if (this.value === 'counter') {
            counterOfferContainer.classList.remove('d-none');
            form.querySelector('input[name="counter_price"]').required = true;
          } else {
            counterOfferContainer.classList.add('d-none');
            form.querySelector('input[name="counter_price"]').required = false;
          }
        });
      });
      
      // Handle modification response
      const modRadioButtons = document.querySelectorAll('input[name="modification_response"]');
      modRadioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
          const form = this.closest('form');
          const rejectionContainer = form.querySelector('.rejection-reason');
          if (this.value === 'reject') {
            rejectionContainer.style.display = 'block';
            form.querySelector('textarea[name="rejection_reason"]').required = true;
          } else {
            rejectionContainer.style.display = 'none';
            form.querySelector('textarea[name="rejection_reason"]').required = false;
          }
        });
      });
    });
  </script>
</body>
</html>