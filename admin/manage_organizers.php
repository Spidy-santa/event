<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get all organizers
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'organizer' ORDER BY is_approved, name");
$organizers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Organizers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="mb-3">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="manage_organizers.php" class="btn btn-secondary">
                <i class="fas fa-users"></i> Manage Organizers
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <h2>Manage Organizers</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizers as $organizer): ?>
                <tr>
                    <td><?= htmlspecialchars($organizer['name']) ?></td>
                    <td><?= htmlspecialchars($organizer['email']) ?></td>
                    <td>
                        <?php if ($organizer['is_approved'] == 1): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($organizer['is_approved'] == 0): ?>
                            <form action="approve_organizer.php" method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $organizer['user_id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            
                            <form action="approve_organizer.php" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Are you sure you want to reject this organizer?');">
                                <input type="hidden" name="user_id" value="<?= $organizer['user_id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="approve_organizer.php" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Are you sure you want to remove this organizer?');">
                                <input type="hidden" name="user_id" value="<?= $organizer['user_id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($organizers)): ?>
                <tr>
                    <td colspan="4" class="text-center">No organizers found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>