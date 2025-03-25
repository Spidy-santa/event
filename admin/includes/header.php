<?php
// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Event Management System</title>
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
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.2);
            color: white;
            border-left: 4px solid #0d6efd;
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .admin-header {
            background: #0d6efd;
            color: white;
            padding: 10px 0;
        }
        .content-wrapper {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 d-flex align-items-center">
                    <i class="fas fa-calendar-check fa-2x me-2"></i>
                    <h4 class="text-white mb-0">Admin Panel</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                            <i class="fas fa-chart-bar me-2"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'manage_organizers.php' ? 'active' : '' ?>" href="manage_organizers.php">
                            <i class="fas fa-users me-2"></i> Manage Organizers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'approve_organizer.php' ? 'active' : '' ?>" href="approve_organizer.php">
                            <i class="fas fa-user-check me-2"></i> Approve Organizers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'manage_events.php' ? 'active' : '' ?>" href="manage_events.php">
                            <i class="fas fa-calendar-alt me-2"></i> Manage Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'manage_event_requests.php' ? 'active' : '' ?>" href="manage_event_requests.php">
                            <i class="fas fa-calendar-plus me-2"></i> Event Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'user_maintenance.php' ? 'active' : '' ?>" href="user_maintenance.php">
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
            <div class="col-md-9 col-lg-10 p-0">
                <div class="admin-header">
                    <div class="container-fluid py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Event Management System</h4>
                            <div>
                                <span><i class="fas fa-user me-2"></i><?= $_SESSION['name'] ?? 'Admin' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-wrapper">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>