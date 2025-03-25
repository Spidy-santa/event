<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get organizer's events
try {
    // Get events that are assigned to this organizer
    $stmt = $pdo->prepare("SELECT event_id as id, title, category as event_type, date, status FROM events 
                         WHERE organizer_id = ? 
                         ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get event requests that have been approved for this organizer
    $req_stmt = $pdo->prepare("SELECT er.id, er.name as title, er.event_type, er.preferred_date as date, er.status, er.event_budget
                            FROM event_requests er 
                            WHERE er.assigned_by = ? AND er.status = 'approved'
                            ORDER BY er.preferred_date DESC");
    $req_stmt->execute([$user_id]);
    $requests = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine both sets of events
    foreach ($requests as &$request) {
        $request['is_request'] = true;
    }
    
    // Merge the arrays
    if (!empty($requests)) {
        $events = array_merge($events, $requests);
        // Sort by date
        usort($events, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }
} catch (PDOException $e) {
    $error_message = "Error retrieving events: " . $e->getMessage();
}

// Get event details if event_id is specified
$selected_event = null;
$budget_items = [];
$total_budget = 0;
$selected_event_id = isset($_GET['event_id']) ? filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT) : null;

if ($selected_event_id) {
    try {
        // First check if it's a regular event
        $stmt = $pdo->prepare("SELECT e.event_id as id, e.title, e.description, e.date, 
                              e.ticket_price, e.total_tickets, e.status, e.category as event_type
                            FROM events e 
                            WHERE e.event_id = ? AND e.organizer_id = ?");
        $stmt->execute([$selected_event_id, $user_id]);
        $selected_event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_event) {
            // Calculate estimated budget for regular events based on ticket revenue
            if (isset($selected_event['ticket_price']) && isset($selected_event['total_tickets'])) {
                $selected_event['total_budget'] = $selected_event['ticket_price'] * $selected_event['total_tickets'];
            } else {
                $selected_event['total_budget'] = 0;
            }
        }
        
        if (!$selected_event) {
            // If not found, check if it's an event request
            $stmt = $pdo->prepare("SELECT er.id, er.name as title, er.event_details as description, 
                                  er.preferred_date as date, er.event_budget as total_budget, 
                                  er.status, er.total_attendees, er.event_type, 1 as is_request
                                FROM event_requests er 
                                WHERE er.id = ? AND er.assigned_by = ? AND er.status = 'approved'");
            $stmt->execute([$selected_event_id, $user_id]);
            $selected_event = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get budget items for the event
        if ($selected_event) {
            $total_budget = $selected_event['total_budget'] ?? 0;
            
            // Get budget items
            $stmt = $pdo->prepare("SELECT * FROM budget_items WHERE event_id = ? ORDER BY created_at DESC");
            $stmt->execute([$selected_event_id]);
            $budget_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_message = "Error retrieving event details: " . $e->getMessage();
    }
}

// Calculate budget summary
$total_spent = 0;
$budget_by_category = [];

foreach ($budget_items as $item) {
    $total_spent += $item['amount'];
    $category = $item['category'];
    if (!isset($budget_by_category[$category])) {
        $budget_by_category[$category] = 0;
    }
    $budget_by_category[$category] += $item['amount'];
}

$remaining_budget = $total_budget - $total_spent;
$budget_percentage = $total_budget > 0 ? ($total_spent / $total_budget) * 100 : 0;

// Define budget categories with icons and colors
$budget_categories = [
    'Venue' => ['icon' => 'fa-building', 'color' => 'primary'],
    'Catering' => ['icon' => 'fa-utensils', 'color' => 'success'],
    'Decoration' => ['icon' => 'fa-paint-brush', 'color' => 'info'],
    'Entertainment' => ['icon' => 'fa-music', 'color' => 'warning'],
    'Photography' => ['icon' => 'fa-camera', 'color' => 'danger'],
    'Staffing' => ['icon' => 'fa-users', 'color' => 'secondary'],
    'Equipment' => ['icon' => 'fa-tools', 'color' => 'dark'],
    'Transportation' => ['icon' => 'fa-car', 'color' => 'light'],
    'Gifts' => ['icon' => 'fa-gift', 'color' => 'primary'],
    'Printing' => ['icon' => 'fa-print', 'color' => 'info'],
    'Misc' => ['icon' => 'fa-clipboard-list', 'color' => 'dark']
];

// Define budget templates based on event types
$budget_templates = [
    'wedding' => [
        'Venue' => 40, // percentage of total budget
        'Catering' => 30,
        'Decoration' => 15,
        'Photography' => 10,
        'Entertainment' => 5
    ],
    'birthday' => [
        'Venue' => 30,
        'Catering' => 35,
        'Decoration' => 15,
        'Entertainment' => 15, 
        'Gifts' => 5
    ],
    'conference' => [
        'Venue' => 35,
        'Catering' => 25,
        'Equipment' => 20,
        'Staffing' => 10,
        'Printing' => 5,
        'Transportation' => 5
    ],
    'workshop' => [
        'Venue' => 30,
        'Catering' => 20,
        'Equipment' => 25,
        'Staffing' => 15,
        'Printing' => 10
    ]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_budget':
                $event_id = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
                $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
                $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

                try {
                    $stmt = $pdo->prepare("INSERT INTO budget_items (event_id, category, amount, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$event_id, $category, $amount, $description]);
                    $success_message = "Budget item added successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error adding budget item: " . $e->getMessage();
                }
                break;

            case 'delete_item':
                $item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
                try {
                    $stmt = $pdo->prepare("DELETE FROM budget_items WHERE id = ? AND event_id IN (SELECT event_id FROM events WHERE organizer_id = ?)");
                    $stmt->execute([$item_id, $user_id]);
                    $success_message = "Budget item deleted successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error deleting budget item: " . $e->getMessage();
                }
                break;
                
            case 'apply_template':
                $event_id = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
                $event_type = filter_input(INPUT_POST, 'event_type', FILTER_SANITIZE_STRING);
                $total_budget = filter_input(INPUT_POST, 'total_budget', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
                // Only proceed if we have a valid template for this event type
                if (isset($budget_templates[$event_type]) && $total_budget > 0) {
                    try {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Delete existing budget items for this event
                        $stmt = $pdo->prepare("DELETE FROM budget_items WHERE event_id = ?");
                        $stmt->execute([$event_id]);
                        
                        // Insert new budget items based on template
                        $insert = $pdo->prepare("INSERT INTO budget_items (event_id, category, amount, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                        
                        foreach ($budget_templates[$event_type] as $category => $percentage) {
                            $amount = ($percentage / 100) * $total_budget;
                            $description = "Auto-generated based on {$event_type} budget template ({$percentage}% allocation)";
                            $insert->execute([$event_id, $category, $amount, $description]);
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        $success_message = "Budget template applied successfully!";
                    } catch (PDOException $e) {
                        // Roll back transaction on error
                        $pdo->rollBack();
                        $error_message = "Error applying budget template: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Cannot apply template: Invalid event type or budget amount.";
                }
                break;
                
            case 'per_person_calculation':
                $event_id = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
                $attendees = filter_input(INPUT_POST, 'attendees', FILTER_SANITIZE_NUMBER_INT);
                $per_person_cost = filter_input(INPUT_POST, 'per_person_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
                
                if ($attendees > 0 && $per_person_cost > 0) {
                    $total_amount = $attendees * $per_person_cost;
                    $description = "Per-person calculation: {$attendees} attendees × ₹{$per_person_cost} each";
                    
                    try {
                        $stmt = $pdo->prepare("INSERT INTO budget_items (event_id, category, amount, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([$event_id, $category, $total_amount, $description]);
                        $success_message = "Per-person budget item added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding per-person budget item: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid attendee count or per-person cost.";
                }
                break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Event Budget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .budget-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .budget-header {
            background-color: #4e73df;
            color: white;
            padding: 1.5rem;
        }
        .budget-content {
            padding: 1.5rem;
        }
        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
        }
        .budget-item {
            border-left: 4px solid #4e73df;
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #f8f9fc;
            border-radius: 0 5px 5px 0;
            transition: all 0.3s;
        }
        .budget-item:hover {
            transform: translateX(5px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        }
        .budget-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .budget-progress {
            height: 20px;
            font-size: 0.75rem;
            background-color: #eaecf4;
        }
        .nav-pills .nav-link.active {
            background-color: #4e73df;
        }
        .summary-card {
            border-left: 4px solid;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-card.budget {
            border-left-color: #4e73df;
        }
        .summary-card.spent {
            border-left-color: #1cc88a;
        }
        .summary-card.remaining {
            border-left-color: #f6c23e;
        }
        .summary-card.alert {
            border-left-color: #e74a3b;
        }
        .summary-icon {
            font-size: 2rem;
            color: #dddfeb;
        }
        .event-card {
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .event-card:hover {
            transform: translateY(-3px);
        }
        .budget-dashboard {
            display: flex;
            flex-wrap: wrap;
        }
        .chart-container {
            flex: 1;
            min-width: 300px;
            margin-bottom: 1.5rem;
        }
        
        /* Enhanced Event Details CSS */
        .event-details {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #4e73df;
            transition: all 0.3s ease;
        }
        .event-details:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .event-details h5 {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid #e3e6f0;
            padding-bottom: 10px;
        }
        .event-details p {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .event-details p strong {
            font-weight: 600;
            color: #5a5c69;
            min-width: 100px;
        }
        .event-details .badge {
            font-size: 85%;
            padding: 5px 10px;
        }
        .event-property {
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e3e6f0;
        }
        .event-property:last-child {
            border-bottom: none;
        }
        .event-type-icon {
            margin-right: 10px;
            width: 30px;
            height: 30px;
            background-color: #4e73df;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .event-description {
            background: #fff;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
            font-style: italic;
            color: #6e707e;
            border: 1px solid #e3e6f0;
        }
        .event-meta {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .event-meta-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
            color: #6e707e;
        }
        .event-meta-item i {
            margin-right: 5px;
            color: #4e73df;
        }
        .event-status {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .event-details-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .event-header {
            background-color: #4e73df;
            color: white;
            padding: 15px 20px;
        }
        .event-content {
            padding: 20px;
        }
        .event-footer {
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
            padding: 10px 20px;
            font-size: 0.875rem;
        }
        .category-label {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 5px;
            background-color: #e3e6f0;
            color: #5a5c69;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-calculator me-2"></i>Budget Management</h1>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-3">
                <div class="budget-card">
                    <div class="budget-header">
                        <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Your Events</h4>
                    </div>
                    <div class="budget-content">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="event_id" class="form-label">Select Event</label>
                                <select name="event_id" id="event_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Choose an event...</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?= $event['id'] ?>" <?= $selected_event_id == $event['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($event['title']) ?>
                                            <?= isset($event['is_request']) ? ' (Request)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <?php if ($selected_event): ?>
                            <div class="event-details-card">
                                <div class="event-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Event Details
                                    </h5>
                                </div>
                                <div class="event-content">
                                    <div class="event-status">
                                        <span class="badge bg-<?= $selected_event['status'] == 'approved' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($selected_event['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="event-meta">
                                        <div class="event-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('F j, Y', strtotime($selected_event['date'])) ?>
                                        </div>
                                        <?php if (isset($selected_event['event_type'])): ?>
                                        <div class="event-meta-item">
                                            <i class="fas fa-tag"></i>
                                            <?= ucfirst($selected_event['event_type']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="event-property">
                                        <strong>Event Name</strong>
                                        <span><?= htmlspecialchars($selected_event['title']) ?></span>
                                    </div>
                                    
                                    <?php if (isset($selected_event['total_attendees'])): ?>
                                    <div class="event-property">
                                        <strong>Attendees</strong>
                                        <span>
                                            <i class="fas fa-users me-2 text-primary"></i>
                                            <?= htmlspecialchars($selected_event['total_attendees']) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="event-property">
                                        <strong>Budget</strong>
                                        <span>
                                            <i class="fas fa-wallet me-2 text-success"></i>
                                            ₹<?= number_format($total_budget, 2) ?>
                                        </span>
                                    </div>

                                    <?php if (isset($selected_event['is_request'])): ?>
                                    <div class="event-property">
                                        <strong>Request Type</strong>
                                        <span>
                                            <i class="fas fa-file-signature me-2 text-info"></i>
                                            Custom Event Request
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($selected_event['description'])): ?>
                                    <div class="event-description">
                                        <?= htmlspecialchars(substr($selected_event['description'] ?? '', 0, 150)) ?>
                                        <?= strlen($selected_event['description']) > 150 ? '...' : '' ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="event-footer">
                                    <small class="text-muted">
                                        <?= isset($selected_event['is_request']) ? 'Request ID' : 'Event ID' ?>: #<?= $selected_event['id'] ?>
                                    </small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center my-5 text-muted">
                                <i class="fas fa-hand-point-up fa-3x mb-3"></i>
                                <p>Please select an event from above to manage its budget</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <?php if ($selected_event): ?>
                    <div class="budget-card">
                        <div class="budget-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-wallet me-2"></i>Budget Dashboard</h4>
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                                <i class="fas fa-plus-circle me-2"></i>Add Budget Item
                            </button>
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#applyTemplateModal">
                                <i class="fas fa-file-alt me-2"></i>Apply Budget Template
                            </button>
                        </div>
                        <div class="budget-content">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="summary-card budget p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="text-primary mb-1">Total Budget</p>
                                                <h4 class="mb-0">₹<?= number_format($total_budget, 2) ?></h4>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card spent p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="text-success mb-1">Total Spent</p>
                                                <h4 class="mb-0">₹<?= number_format($total_spent, 2) ?></h4>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card <?= $remaining_budget < 0 ? 'alert' : 'remaining' ?> p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="<?= $remaining_budget < 0 ? 'text-danger' : 'text-warning' ?> mb-1">
                                                    <?= $remaining_budget < 0 ? 'Over Budget' : 'Remaining' ?>
                                                </p>
                                                <h4 class="mb-0">₹<?= number_format(abs($remaining_budget), 2) ?></h4>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-<?= $remaining_budget < 0 ? 'exclamation-triangle' : 'piggy-bank' ?>"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress budget-progress mb-4">
                                <div class="progress-bar progress-bar-striped 
                                    <?= $budget_percentage > 100 ? 'bg-danger' : 'bg-success' ?>" 
                                    role="progressbar" 
                                    style="width: <?= min($budget_percentage, 100) ?>%" 
                                    aria-valuenow="<?= $budget_percentage ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?= round($budget_percentage) ?>%
                                </div>
                            </div>
                            
                            <ul class="nav nav-pills mb-4" id="budgetTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="items-tab" data-bs-toggle="pill" 
                                            data-bs-target="#items" type="button" role="tab" 
                                            aria-controls="items" aria-selected="true">
                                        Budget Items
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="reports-tab" data-bs-toggle="pill" 
                                            data-bs-target="#reports" type="button" role="tab" 
                                            aria-controls="reports" aria-selected="false">
                                        Budget Reports
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="budgetTabContent">
                                <div class="tab-pane fade show active" id="items" role="tabpanel" aria-labelledby="items-tab">
                                    <?php if (empty($budget_items)): ?>
                                        <div class="text-center my-5 text-muted">
                                            <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                                            <p>No budget items added yet. Click "Add Budget Item" to get started.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Description</th>
                                                        <th>Amount</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($budget_items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php 
                                                                    $icon = isset($budget_categories[$item['category']]) 
                                                                        ? $budget_categories[$item['category']]['icon'] 
                                                                        : 'fa-tag';
                                                                    $color = isset($budget_categories[$item['category']]) 
                                                                        ? $budget_categories[$item['category']]['color'] 
                                                                        : 'secondary';
                                                                    ?>
                                                                    <span class="category-icon bg-<?= $color ?>">
                                                                        <i class="fas <?= $icon ?>"></i>
                                                                    </span>
                                                                    <?= htmlspecialchars($item['category']) ?>
                                                                </div>
                                                            </td>
                                                            <td><?= htmlspecialchars($item['description']) ?></td>
                                                            <td class="budget-amount">₹<?= number_format($item['amount'], 2) ?></td>
                                                            <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
                                                            <td>
                                                                <form method="POST" style="display: inline">
                                                                    <input type="hidden" name="action" value="delete_item">
                                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="return confirm('Are you sure you want to delete this item?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                                    <div class="budget-dashboard">
                                        <div class="chart-container">
                                            <h5 class="mb-3">Budget Breakdown</h5>
                                            <canvas id="budgetPieChart"></canvas>
                                        </div>
                                        
                                        <div class="chart-container">
                                            <h5 class="mb-3">Spending by Category</h5>
                                            <canvas id="categoryBarChart"></canvas>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h5 class="mb-3">Category Summary</h5>
                                        <?php if (!empty($budget_by_category)): ?>
                                            <div class="row">
                                                <?php foreach ($budget_by_category as $category => $amount): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="budget-item">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <?php 
                                                                    $icon = isset($budget_categories[$category]) 
                                                                        ? $budget_categories[$category]['icon'] 
                                                                        : 'fa-tag';
                                                                    $color = isset($budget_categories[$category]) 
                                                                        ? $budget_categories[$category]['color'] 
                                                                        : 'secondary';
                                                                    ?>
                                                                    <span class="category-icon bg-<?= $color ?> d-inline-flex" style="width: 30px; height: 30px">
                                                                        <i class="fas <?= $icon ?> fa-sm"></i>
                                                                    </span>
                                                                    <span class="ms-2"><?= htmlspecialchars($category) ?></span>
                                                                </div>
                                                                <div class="budget-amount">₹<?= number_format($amount, 2) ?></div>
                                                            </div>
                                                            <div class="mt-2">
                                                                <div class="progress" style="height: 10px;">
                                                                    <div class="progress-bar bg-<?= $color ?>" 
                                                                         style="width: <?= ($amount / $total_spent) * 100 ?>%" 
                                                                         role="progressbar"></div>
                                                                </div>
                                                                <small class="text-muted"><?= round(($amount / $total_spent) * 100) ?>% of total</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center my-4 text-muted">
                                                <p>No budget data available for reports.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Budget Modal -->
    <div class="modal fade" id="addBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Budget Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addBudgetForm">
                        <input type="hidden" name="action" value="add_budget">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($budget_categories as $category => $details): ?>
                                    <option value="<?= $category ?>">
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#perPersonModal" data-bs-dismiss="modal">
                                <i class="fas fa-calculator me-2"></i>Per-Person Calculation
                            </button>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply Template Modal -->
    <div class="modal fade" id="applyTemplateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply Budget Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="applyTemplateForm">
                        <input type="hidden" name="action" value="apply_template">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        
                        <div class="mb-3">
                            <label for="event_type" class="form-label">Event Type</label>
                            <select name="event_type" id="event_type" class="form-select" required>
                                <option value="">Select an event type...</option>
                                <?php foreach ($budget_templates as $event_type => $template): ?>
                                    <option value="<?= $event_type ?>">
                                        <?= ucfirst($event_type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_budget" class="form-label">Total Budget (₹)</label>
                            <input type="number" step="0.01" min="0" name="total_budget" id="total_budget" class="form-control" required>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Apply Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Per-Person Pricing Modal -->
    <div class="modal fade" id="perPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Per-Person Pricing Calculator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="perPersonForm">
                        <input type="hidden" name="action" value="per_person_calculation">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        
                        <div class="mb-3">
                            <label for="pp_category" class="form-label">Budget Category</label>
                            <select name="category" id="pp_category" class="form-select" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($budget_categories as $category => $details): ?>
                                    <option value="<?= $category ?>">
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attendees" class="form-label">Number of Attendees</label>
                            <input type="number" min="1" name="attendees" id="attendees" class="form-control" 
                                   value="<?= $selected_event['total_attendees'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="per_person_cost" class="form-label">Cost Per Person (₹)</label>
                            <input type="number" step="0.01" min="0" name="per_person_cost" id="per_person_cost" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_calculation" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" id="total_calculation" class="form-control" readonly>
                            </div>
                            <small class="text-muted">This is automatically calculated based on attendees and per-person cost.</small>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addBudgetModal">Back</button>
                            <button type="submit" class="btn btn-primary">Add to Budget</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Budget Pie Chart
        const budgetPieChart = document.getElementById('budgetPieChart').getContext('2d');
        const budgetPieChartData = {
            labels: <?= json_encode(array_keys($budget_by_category)) ?>,
            datasets: [{
                label: 'Budget Breakdown',
                data: <?= json_encode(array_values($budget_by_category)) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                ],
                borderWidth: 1
            }]
        };
        const budgetPieChartOptions = {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };
        new Chart(budgetPieChart, {
            type: 'pie',
            data: budgetPieChartData,
            options: budgetPieChartOptions
        });

        // Category Bar Chart
        const categoryBarChart = document.getElementById('categoryBarChart').getContext('2d');
        const categoryBarChartData = {
            labels: <?= json_encode(array_keys($budget_by_category)) ?>,
            datasets: [{
                label: 'Spending by Category',
                data: <?= json_encode(array_values($budget_by_category)) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                ],
                borderWidth: 1
            }]
        };
        const categoryBarChartOptions = {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };
        new Chart(categoryBarChart, {
            type: 'bar',
            data: categoryBarChartData,
            options: categoryBarChartOptions
        });
        
        // Real-time per-person calculation
        document.addEventListener('DOMContentLoaded', function() {
            const attendeesInput = document.getElementById('attendees');
            const perPersonCostInput = document.getElementById('per_person_cost');
            const totalCalculationInput = document.getElementById('total_calculation');
            
            function updateTotalCalculation() {
                const attendees = parseFloat(attendeesInput.value) || 0;
                const perPersonCost = parseFloat(perPersonCostInput.value) || 0;
                const total = attendees * perPersonCost;
                
                totalCalculationInput.value = total.toFixed(2);
            }
            
            attendeesInput.addEventListener('input', updateTotalCalculation);
            perPersonCostInput.addEventListener('input', updateTotalCalculation);
        });
    </script>
</body>
</html>
