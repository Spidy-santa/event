<?php
session_start();
require '../includes/db.php';

// Restrict to admins
if ($_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch users
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

// Approve/Reject Organizers
if (isset($_GET['action']) && isset($_GET['user_id'])) {
  $user_id = $_GET['user_id'];
  $action = $_GET['action'];

  if ($action === 'approve') {
    $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ?")->execute([$user_id]);
  } elseif ($action === 'reject') {
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$user_id]);
  }
  header("Location: manage_users.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="container mt-5">
  <header>
    <h1>Manage Users</h1>
  </header>
  <h2>Manage Users</h2>
  <table class="table">
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
      <?php foreach($users as $user): ?>
      <tr>
        <td><?= $user['name'] ?></td>
        <td><?= $user['email'] ?></td>
        <td><?= $user['role'] ?></td>
        <td><?= $user['is_approved'] ? 'Approved' : 'Pending' ?></td>
        <td>
          <?php if ($user['role'] === 'organizer' && !$user['is_approved']): ?>
            <a href="manage_users.php?action=approve&user_id=<?= $user['user_id'] ?>" class="btn btn-success">Approve</a>
            <a href="manage_users.php?action=reject&user_id=<?= $user['user_id'] ?>" class="btn btn-danger">Reject</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button>Add User</button>
  <button>Edit User</button>
  <button>Delete User</button>
  <script src="assets/js/admin.js"></script>
</body>
</html>