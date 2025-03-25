<?php
session_start();
require '../includes/db.php';

// Create request_modifications table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS request_modifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            modified_by INT NOT NULL,
            modification_note TEXT NOT NULL,
            previous_status VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
$pdo->exec($query);

// Only allow admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Process the update request
if (isset($_POST['update_request'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $budget = $_POST['event_budget'];
    $admin_comments = $_POST['admin_comments'] ?? '';
    
    // Get the current request information to track modifications
    $current_stmt = $pdo->prepare("SELECT * FROM event_requests WHERE id = ?");
    $current_stmt->execute([$request_id]);
    $current_request = $current_stmt->fetch();
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        if ($status === 'quotation') {
            $price_quote = $_POST['price_quote'] ?? $budget;
            
            // Update with quotation
            $stmt = $pdo->prepare("UPDATE event_requests 
                                  SET status = ?, 
                                      event_budget = ?, 
                                      price_quote = ?, 
                                      admin_comments = ?,
                                      is_admin_modified = 0,
                                      updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$status, $budget, $price_quote, $admin_comments, $request_id]);
            
            // Add to modification history
            $mod_note = "Admin sent price quotation of ₹" . number_format($price_quote, 2);
            $prev_status = $current_request['status'];
            
            $history_stmt = $pdo->prepare("INSERT INTO request_modifications 
                                          (request_id, modified_by, modification_note, previous_status)
                                          VALUES (?, ?, ?, ?)");
            $history_stmt->execute([$request_id, $_SESSION['user_id'], $mod_note, $prev_status]);
            
            $_SESSION['success'] = "Price quotation sent to client successfully!";
            
        } elseif ($status === 'modified') {
            // Get the new data for modification
            $event_type = $_POST['event_type'] ?? $current_request['event_type'];
            $event_details = $_POST['event_details'] ?? $current_request['event_details'];
            $preferred_date = $_POST['preferred_date'] ?? $current_request['preferred_date'];
            $location = $_POST['location'] ?? $current_request['location'];
            $total_attendees = $_POST['total_attendees'] ?? $current_request['total_attendees'];
            
            // Check if anything was actually modified
            $changes = [];
            if ($event_type != $current_request['event_type']) $changes[] = "Event Type: " . $event_type;
            if ($preferred_date != $current_request['preferred_date']) $changes[] = "Date: " . $preferred_date;
            if ($location != $current_request['location']) $changes[] = "Location: " . $location;
            if ($total_attendees != $current_request['total_attendees']) $changes[] = "Attendees: " . $total_attendees;
            if ($budget != $current_request['event_budget']) $changes[] = "Budget: ₹" . number_format($budget, 2);
            if ($event_details != $current_request['event_details']) $changes[] = "Event Details Updated";
            
            // Update with modifications and set admin_modified flag
            $stmt = $pdo->prepare("UPDATE event_requests 
                                  SET status = ?, 
                                      event_budget = ?, 
                                      admin_comments = ?,
                                      event_type = ?,
                                      event_details = ?,
                                      preferred_date = ?,
                                      location = ?,
                                      total_attendees = ?,
                                      is_admin_modified = 1,
                                      attendee_response = NULL,
                                      updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([
                $status, 
                $budget, 
                $admin_comments,
                $event_type,
                $event_details,
                $preferred_date,
                $location,
                $total_attendees,
                $request_id
            ]);
            
            // Create modification note
            $mod_note = empty($changes) 
                ? "Admin requested changes without modifying details" 
                : "Admin modified request: " . implode(", ", $changes);
                
            if (!empty($admin_comments)) {
                $mod_note .= "\n\nComments: " . $admin_comments;
            }
            
            $prev_status = $current_request['status'];
            
            $history_stmt = $pdo->prepare("INSERT INTO request_modifications 
                                          (request_id, modified_by, modification_note, previous_status)
                                          VALUES (?, ?, ?, ?)");
            $history_stmt->execute([$request_id, $_SESSION['user_id'], $mod_note, $prev_status]);
            
            // Create notification for the user about the modification
            $notify_stmt = $pdo->prepare("INSERT INTO notifications 
                                       (user_id, title, message, created_at) 
                                       VALUES (?, ?, ?, NOW())");
            $notify_title = "Event Request Modified";
            $notify_msg = "Your event request #" . $request_id . " has been modified by an admin. Please review the changes.";
            $notify_stmt->execute([$current_request['user_id'], $notify_title, $notify_msg]);
            
            $_SESSION['success'] = "Event request modified and sent to client for approval!";
            
        } elseif ($status === 'approved') {
            // Handle if we're approving a counter offer
            if ($current_request['status'] == 'counter_offer' && !empty($current_request['user_price_counter'])) {
                // Accept the counter offer price
                $final_price = $current_request['user_price_counter'];
                
                $stmt = $pdo->prepare("UPDATE event_requests 
                                      SET status = ?, 
                                          event_budget = ?, 
                                          admin_comments = ?,
                                          admin_final_price = ?,
                                          is_admin_modified = 0,
                                          updated_at = NOW()
                                      WHERE id = ?");
                $stmt->execute([$status, $budget, $admin_comments, $final_price, $request_id]);
                
                // Add to modification history
                $mod_note = "Admin accepted client counter offer of ₹" . number_format($final_price, 2);
                $prev_status = $current_request['status'];
                
                $history_stmt = $pdo->prepare("INSERT INTO request_modifications 
                                              (request_id, modified_by, modification_note, previous_status)
                                              VALUES (?, ?, ?, ?)");
                $history_stmt->execute([$request_id, $_SESSION['user_id'], $mod_note, $prev_status]);
                
                // Create notification for the client
                $notify_stmt = $pdo->prepare("INSERT INTO notifications 
                                          (user_id, title, message, created_at) 
                                          VALUES (?, ?, ?, NOW())");
                $notify_title = "Counter Offer Accepted";
                $notify_msg = "Your counter offer for event request #" . $request_id . " has been accepted by the admin.";
                $notify_stmt->execute([$current_request['user_id'], $notify_title, $notify_msg]);
                
            } else {
                // Regular approval
                $stmt = $pdo->prepare("UPDATE event_requests 
                                      SET status = ?, 
                                          event_budget = ?, 
                                          admin_comments = ?,
                                          is_admin_modified = 0,
                                          updated_at = NOW()
                                      WHERE id = ?");
                $stmt->execute([$status, $budget, $admin_comments, $request_id]);
                
                // Add to modification history if coming from a modified state with attendee response
                if (!empty($current_request['attendee_response'])) {
                    $mod_note = "Admin approved request after attendee response: " . $current_request['attendee_response'];
                    $prev_status = $current_request['status'];
                    
                    $history_stmt = $pdo->prepare("INSERT INTO request_modifications 
                                                  (request_id, modified_by, modification_note, previous_status)
                                                  VALUES (?, ?, ?, ?)");
                    $history_stmt->execute([$request_id, $_SESSION['user_id'], $mod_note, $prev_status]);
                }
                
                // Create notification for the client
                $notify_stmt = $pdo->prepare("INSERT INTO notifications 
                                         (user_id, title, message, created_at) 
                                         VALUES (?, ?, ?, NOW())");
                $notify_title = "Event Request Approved";
                $notify_msg = "Your event request #" . $request_id . " has been approved by the admin.";
                $notify_stmt->execute([$current_request['user_id'], $notify_title, $notify_msg]);
            }
            
            $_SESSION['success'] = "Event request approved successfully!";
            
        } elseif ($status === 'rejected') {
            // Handle rejection
            $stmt = $pdo->prepare("UPDATE event_requests 
                                  SET status = ?, 
                                      event_budget = ?, 
                                      admin_comments = ?,
                                      is_admin_modified = 0,
                                      updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$status, $budget, $admin_comments, $request_id]);
            
            // Create notification for the client
            $notify_stmt = $pdo->prepare("INSERT INTO notifications 
                                     (user_id, title, message, created_at) 
                                     VALUES (?, ?, ?, NOW())");
            $notify_title = "Event Request Rejected";
            $notify_msg = "Your event request #" . $request_id . " has been rejected by the admin.";
            $notify_stmt->execute([$current_request['user_id'], $notify_title, $notify_msg]);
            
            $_SESSION['success'] = "Event request rejected.";
            
        } else {
            // Handle pending or any other status
            $stmt = $pdo->prepare("UPDATE event_requests 
                                  SET status = ?, 
                                      event_budget = ?, 
                                      admin_comments = ?,
                                      updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$status, $budget, $admin_comments, $request_id]);
            
            $_SESSION['success'] = "Event request updated successfully!";
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect to avoid form resubmission
        header('Location: manage_event_requests.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating request: " . $e->getMessage();
        header('Location: manage_event_requests.php');
        exit();
    }
}

// Set page title for the header
$page_title = "Manage Event Requests";

// Now it's safe to include header
if (file_exists('includes/header.php')) {
    include 'includes/header.php';
} else {
    // Simple header if the include doesn't exist
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Admin - Manage Event Requests</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background-color: #f8f9fa;
                font-family: "Times New Roman", Times, serif;
            }
            .card {
                border-radius: 0;
                border: 1px solid #ddd;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .table th {
                font-family: Georgia, serif;
                background-color: #343a40;
                color: white;
            }
            .btn {
                border-radius: 0;
            }
            .btn-primary {
                background-color: #2c3e50;
                border-color: #2c3e50;
            }
            .btn-info {
                background-color: #3498db;
                border-color: #3498db;
            }
            h2 {
                font-family: Georgia, serif;
                border-bottom: 2px solid #343a40;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .modal-header {
                background-color: #343a40;
                color: white;
            }
            .negotiation-history {
                background-color: #f8f9fa;
                border-left: 3px solid #6c757d;
                padding: 10px 15px;
                margin: 15px 0;
            }
            .counter-offer {
                color: #dc3545;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 col-lg-10 p-4">';
}

// Fetch event requests with user details
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$event_type_filter = isset($_GET['event_type']) ? $_GET['event_type'] : '';

$query = "SELECT er.*, u.email, u.name as user_name 
          FROM event_requests er
          LEFT JOIN users u ON er.user_id = u.user_id";

$where_clauses = [];
$params = [];

if (!empty($status_filter)) {
    $where_clauses[] = "er.status = ?";
    $params[] = $status_filter;
}

if (!empty($event_type_filter)) {
    $where_clauses[] = "er.event_type = ?";
    $params[] = $event_type_filter;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY er.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Fetch modification history for all requests
$mod_history = [];
$history_query = "SELECT rm.*, u.name as modifier_name
                  FROM request_modifications rm
                  LEFT JOIN users u ON rm.modified_by = u.user_id
                  ORDER BY rm.created_at DESC";
$history_stmt = $pdo->prepare($history_query);
$history_stmt->execute();
$histories = $history_stmt->fetchAll();

// Group histories by request_id
foreach ($histories as $history) {
    if (!isset($mod_history[$history['request_id']])) {
        $mod_history[$history['request_id']] = [];
    }
    $mod_history[$history['request_id']][] = $history;
}

?>

<div class="container mt-4">
    <h1 class="mb-4">Manage Event Requests</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Requests</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="modified" <?= $status_filter == 'modified' ? 'selected' : '' ?>>Modified</option>
                        <option value="quotation" <?= $status_filter == 'quotation' ? 'selected' : '' ?>>Quotation</option>
                        <option value="counter_offer" <?= $status_filter == 'counter_offer' ? 'selected' : '' ?>>Counter Offer</option>
                        <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="event_type" class="form-label">Event Type</label>
                    <select name="event_type" id="event_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="wedding" <?= $event_type_filter == 'wedding' ? 'selected' : '' ?>>Wedding</option>
                        <option value="birthday" <?= $event_type_filter == 'birthday' ? 'selected' : '' ?>>Birthday</option>
                        <option value="conference" <?= $event_type_filter == 'conference' ? 'selected' : '' ?>>Conference</option>
                        <option value="workshop" <?= $event_type_filter == 'workshop' ? 'selected' : '' ?>>Workshop</option>
                        <option value="other" <?= $event_type_filter == 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="manage_event_requests.php" class="btn btn-outline-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (empty($requests)): ?>
    <div class="alert alert-info">No event requests found.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="requestsTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Requester</th>
                    <th>Event Type</th>
                    <th>Preferred Date</th>
                    <th>Attendees</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr data-status="<?= $request['status'] ?>" data-event-type="<?= $request['event_type'] ?>">
                    <td><?= $request['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($request['name']) ?></strong><br>
                        <small><?= htmlspecialchars($request['contact']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(ucfirst($request['event_type'])) ?></td>
                    <td><?= date('M j, Y', strtotime($request['preferred_date'])) ?></td>
                    <td><?= htmlspecialchars($request['total_attendees']) ?></td>
                    <td>₹<?= number_format($request['event_budget'] ?? 0) ?></td>
                    <td>
                        <?php 
                        $statusClass = 'bg-secondary';
                        $statusText = ucfirst($request['status']);
                        
                        if ($request['status'] == 'pending') {
                            $statusClass = 'bg-warning';
                        } elseif ($request['status'] == 'approved' || $request['status'] == 'accepted') {
                            $statusClass = 'bg-success';
                            $statusText = 'Approved';
                        } elseif ($request['status'] == 'rejected') {
                            $statusClass = 'bg-danger';
                        } elseif ($request['status'] == 'quotation') {
                            $statusClass = 'bg-info';
                            $statusText = 'Quote Sent';
                        } elseif ($request['status'] == 'counter_offer') {
                            $statusClass = 'bg-primary';
                            $statusText = 'Counter Offer';
                        }
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $request['id'] ?>">
                            <i class="fas fa-eye"></i> Details
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?= $request['id'] ?>">
                            <i class="fas fa-edit"></i> Update
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php 
    // Create modals for each request
    foreach ($requests as $request): 
    ?>
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal<?= $request['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Event Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($request['name']) ?></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($request['contact']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($request['address']) ?></p>
                            <p><strong>Event Type:</strong> <?= htmlspecialchars(ucfirst($request['event_type'])) ?></p>
                            <p><strong>Total Attendees:</strong> <?= htmlspecialchars($request['total_attendees']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Preferred Date:</strong> <?= date('F j, Y', strtotime($request['preferred_date'])) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($request['location']) ?></p>
                            <p><strong>Budget:</strong> ₹<?= number_format($request['event_budget'] ?? 0) ?></p>
                            <p><strong>Status:</strong> <span class="badge <?= $statusClass ?>"><?= $statusText ?></span></p>
                            <p><strong>Created:</strong> <?= date('F j, Y', strtotime($request['created_at'])) ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Event Details:</h6>
                            <p><?= nl2br(htmlspecialchars($request['event_details'])) ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($request['admin_comments'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Admin Comments:</h6>
                            <p><?= nl2br(htmlspecialchars($request['admin_comments'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] == 'quotation'): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6>Price Quote Sent: ₹<?= number_format($request['price_quote'] ?? 0, 2) ?></h6>
                                <p class="mb-0">Waiting for client response.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] == 'counter_offer' && isset($request['user_price_counter'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="negotiation-history">
                                <h6>Negotiation History</h6>
                                <p><strong>Your Quote:</strong> ₹<?= number_format($request['price_quote'] ?? 0, 2) ?></p>
                                <p><strong>Client Counter Offer:</strong> <span class="counter-offer">₹<?= number_format($request['user_price_counter'], 2) ?></span></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#updateModal<?= $request['id'] ?>">
                        Update Request
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Modal -->
    <div class="modal fade" id="updateModal<?= $request['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Update Event Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status:</label>
                            <select name="status" id="status<?= $request['id'] ?>" class="form-select" required>
                                <option value="pending" <?= $request['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="quotation" <?= $request['status'] == 'quotation' ? 'selected' : '' ?>>Send Quotation</option>
                                <option value="approved" <?= $request['status'] == 'approved' ? 'selected' : '' ?>>Approve</option>
                                <option value="rejected" <?= $request['status'] == 'rejected' ? 'selected' : '' ?>>Reject</option>
                                <option value="modified" <?= $request['status'] == 'modified' ? 'selected' : '' ?>>Modify Request</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="event_budget" class="form-label">Event Budget (₹):</label>
                            <input type="number" name="event_budget" id="event_budget<?= $request['id'] ?>" class="form-control" value="<?= $request['event_budget'] ?? 0 ?>" min="0" step="0.01">
                        </div>
                        
                        <div class="mb-3 quotation-fields" id="quotation-fields<?= $request['id'] ?>" style="<?= $request['status'] == 'quotation' ? '' : 'display:none;' ?>">
                            <label for="price_quote<?= $request['id'] ?>" class="form-label">Price Quote (₹):</label>
                            <input type="number" class="form-control" id="price_quote<?= $request['id'] ?>" name="price_quote" min="0" step="0.01" value="<?= $request['price_quote'] ?? $request['event_budget'] ?? 0 ?>">
                            <small class="form-text text-muted">The price quote will be sent to the client for approval.</small>
                        </div>
                        
                        <div class="mb-3 modification-fields" id="modification-fields<?= $request['id'] ?>" style="<?= $request['status'] == 'modified' ? '' : 'display:none;' ?>">
                            <div class="alert alert-info">
                                <small><i class="bi bi-info-circle"></i> Modifying an event request will send it back to the attendee for approval.</small>
                            </div>
                        
                            <label for="event_type" class="form-label">Event Type:</label>
                            <select name="event_type" id="event_type<?= $request['id'] ?>" class="form-select">
                                <option value="wedding" <?= $request['event_type'] == 'wedding' ? 'selected' : '' ?>>Wedding</option>
                                <option value="birthday" <?= $request['event_type'] == 'birthday' ? 'selected' : '' ?>>Birthday</option>
                                <option value="conference" <?= $request['event_type'] == 'conference' ? 'selected' : '' ?>>Conference</option>
                                <option value="workshop" <?= $request['event_type'] == 'workshop' ? 'selected' : '' ?>>Workshop</option>
                                <option value="other" <?= $request['event_type'] == 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            
                            <label for="event_details" class="form-label">Event Details:</label>
                            <textarea name="event_details" id="event_details<?= $request['id'] ?>" class="form-control" rows="4"><?= htmlspecialchars($request['event_details'] ?? '') ?></textarea>
                            
                            <label for="preferred_date" class="form-label">Preferred Date:</label>
                            <input type="date" name="preferred_date" id="preferred_date<?= $request['id'] ?>" class="form-control" value="<?= $request['preferred_date'] ?? '' ?>">
                            
                            <label for="location" class="form-label">Location:</label>
                            <input type="text" name="location" id="location<?= $request['id'] ?>" class="form-control" value="<?= htmlspecialchars($request['location'] ?? '') ?>">
                            
                            <label for="total_attendees" class="form-label">Total Attendees:</label>
                            <input type="number" name="total_attendees" id="total_attendees<?= $request['id'] ?>" class="form-control" value="<?= $request['total_attendees'] ?? 0 ?>" min="0">
                        </div>
                        
                        <?php if ($request['status'] == 'counter_offer'): ?>
                        <div class="negotiation-history mb-3">
                            <h6>Client Counter Offer</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-1"><strong>Counter Price:</strong> ₹<?= number_format($request['user_price_counter'], 2) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($request['attendee_response'])): ?>
                        <div class="attendee-response mb-3">
                            <h6>Client Response to Modifications</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="mb-1"><?= nl2br(htmlspecialchars($request['attendee_response'])) ?></p>
                                <small class="text-muted">Submitted on: <?= date('M j, Y g:i A', strtotime($request['updated_at'])) ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($mod_history[$request['id']]) && !empty($mod_history[$request['id']])): ?>
                        <div class="modification-history mb-3">
                            <h6>Modification History</h6>
                            <div class="accordion" id="modHistory<?= $request['id'] ?>">
                                <?php foreach($mod_history[$request['id']] as $index => $history): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modCollapse<?= $request['id'] ?>_<?= $index ?>">
                                            <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?> - 
                                            <?= htmlspecialchars($history['modifier_name'] ?? 'System') ?>
                                        </button>
                                    </h2>
                                    <div id="modCollapse<?= $request['id'] ?>_<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#modHistory<?= $request['id'] ?>">
                                        <div class="accordion-body">
                                            <p><?= nl2br(htmlspecialchars($history['modification_note'])) ?></p>
                                            <small class="text-muted">Previous status: <?= ucfirst($history['previous_status']) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="admin_comments<?= $request['id'] ?>" class="form-label">Comments/Feedback:</label>
                            <textarea name="admin_comments" id="admin_comments<?= $request['id'] ?>" class="form-control" rows="4"><?= htmlspecialchars($request['admin_comments'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_request" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    <a href="manage_prices.php" class="btn btn-primary ms-2">Manage Service Prices</a>
</div>

<?php
// Include footer if it exists
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
    echo '</div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Show/hide quotation fields based on status selection
            const statusSelects = document.querySelectorAll("select[id^=\'status\']");
            statusSelects.forEach(select => {
                select.addEventListener("change", function() {
                    const requestId = this.id.replace("status", "");
                    const quotationFields = document.getElementById("quotation-fields" + requestId);
                    const modificationFields = document.getElementById("modification-fields" + requestId);
                    
                    if (this.value === "quotation") {
                        quotationFields.style.display = "block";
                        modificationFields.style.display = "none";
                    } else if (this.value === "modified") {
                        quotationFields.style.display = "none";
                        modificationFields.style.display = "block";
                    } else {
                        quotationFields.style.display = "none";
                        modificationFields.style.display = "none";
                    }
                });
            });
            
            // Table filtering
            const applyFilters = document.getElementById("applyFilters");
            const resetFilters = document.getElementById("resetFilters");
            const statusFilter = document.getElementById("statusFilter");
            const eventTypeFilter = document.getElementById("eventTypeFilter");
            const tableRows = document.querySelectorAll("#requestsTable tbody tr");
            
            applyFilters.addEventListener("click", function() {
                const statusValue = statusFilter.value;
                const eventTypeValue = eventTypeFilter.value;
                
                tableRows.forEach(row => {
                    const rowStatus = row.getAttribute("data-status");
                    const rowEventType = row.getAttribute("data-event-type");
                    let showRow = true;
                    
                    if (statusValue && rowStatus !== statusValue) {
                        showRow = false;
                    }
                    
                    if (eventTypeValue && rowEventType !== eventTypeValue) {
                        showRow = false;
                    }
                    
                    row.style.display = showRow ? "" : "none";
                });
            });
            
            resetFilters.addEventListener("click", function() {
                statusFilter.value = "";
                eventTypeFilter.value = "";
                
                tableRows.forEach(row => {
                    row.style.display = "";
                });
            });
        });
    </script>
    </body>
    </html>';
}
?>