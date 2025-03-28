<?php
session_start();
include '../includes/db.php';

// Only allow admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'delete':
                try {
                    $pdo->beginTransaction();
                    
                    // Check if user exists and is not an admin
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $userToDelete = $stmt->fetch();
                    
                    if (!$userToDelete || $userToDelete['role'] === 'admin') {
                        throw new Exception("Cannot delete this user");
                    }
                    
                    // Delete user's registrations
                    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete events created by this user
                    $stmt = $pdo->prepare("DELETE FROM events WHERE organizer_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    $_SESSION['success'] = "User and all related data deleted successfully.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
                }
                break;
                
            case 'change_role':
                $new_role = $_POST['new_role'];
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $stmt->execute([$new_role, $user_id]);
                $_SESSION['success'] = "User role updated successfully.";
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User status updated successfully.";
                break;
        }
        
        header("Location: user_maintenance.php");
        exit();
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT *, COALESCE(is_active, 0) as is_active FROM users ORDER BY role, name");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
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
    </style>
</head>
<body>
    <header>
        <h1>User Maintenance</h1>
    </header>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-white">Admin Panel</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="user_maintenance.php">
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
                <h2 class="mb-4">User Maintenance</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> User Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'organizer' ? 'primary' : 'secondary') ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= isset($user['is_active']) && $user['is_active'] ? 'success' : 'warning' ?>">
                                                    <?= isset($user['is_active']) && $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <form method="POST" class="dropdown-item">
                                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                                <input type="hidden" name="action" value="toggle_status">
                                                                <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                                                    <i class="fas fa-toggle-on me-2"></i>
                                                                    Toggle Status
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                            <li>
                                                                <form method="POST" class="dropdown-item">
                                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                                    <input type="hidden" name="action" value="change_role">
                                                                    <input type="hidden" name="new_role" value="<?= $user['role'] === 'organizer' ? 'attendee' : 'organizer' ?>">
                                                                    <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                                                        <i class="fas fa-exchange-alt me-2"></i>
                                                                        Change Role
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" class="dropdown-item" onsubmit="return confirmUserDelete('<?= htmlspecialchars($user['name']) ?>');">
                                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button type="submit" class="btn btn-link p-0 text-decoration-none text-danger">
                                                                        <i class="fas fa-trash-alt me-2"></i>
                                                                        Delete User
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button>Update User</button>
    <button>Delete User</button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        function confirmUserDelete(userName) {
            return confirm(`Are you sure you want to delete ${userName}?`);
        }
    </script>
</body>
</html>