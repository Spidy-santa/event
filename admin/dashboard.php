<?php
session_start();
include '../includes/db.php';

// Only allow admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch statistics for dashboard
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'organizer' AND is_approved = 0");
$pending_organizers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
$total_events = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
$total_registrations = $stmt->fetch()['total'];

// Get count of pending event requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM event_requests WHERE status = 'pending'");
$pending_requests = $stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.2);
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-white">Admin Panel</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-bar me-2"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_organizers.php">
                            <i class="fas fa-users me-2"></i> Manage Organizers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approve_organizer.php">
                            <i class="fas fa-user-check me-2"></i> Approve Organizers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_events.php">
                            <i class="fas fa-calendar-alt me-2"></i> Manage Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_event_requests.php">
                            <i class="fas fa-clipboard-list me-2"></i> Event Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_prices.php">
                            <i class="fas fa-dollar-sign me-2"></i> Manage Prices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_maintenance.php">
                            <i class="fas fa-user-cog me-2"></i> User Maintenance
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <a href="approve_organizer.php" class="text-decoration-none">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Organizers</h5>
                                    <h2><?= $pending_organizers ?></h2>
                                    <p class="mb-0"><i class="fas fa-user-clock"></i> Awaiting Approval</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="manage_events.php" class="text-decoration-none">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Events</h5>
                                    <h2><?= $total_events ?></h2>
                                    <p class="mb-0"><i class="fas fa-calendar-check"></i> Events Created</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="user_maintenance.php" class="text-decoration-none">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2><?= $total_users ?></h2>
                                    <p class="mb-0"><i class="fas fa-users"></i> Registered Users</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="manage_event_requests.php" class="text-decoration-none">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Event Requests</h5>
                                    <h2><?= $pending_requests ?></h2>
                                    <p class="mb-0"><i class="fas fa-clipboard-list"></i> Pending Requests</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="approve_organizer.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user-check"></i> Review Organizer Applications
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="manage_events.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-calendar-plus"></i> Manage Events
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="manage_event_requests.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-clipboard-list"></i> Event Requests
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>