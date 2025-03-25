<?php
session_start();
include '../includes/db.php';

// Restrict to organizers
if ($_SESSION['role'] !== 'organizer') {
  header("Location: ../login.php");
  exit();
}

// Fetch organizer's events
$organizer_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ?");
$stmt->execute([$organizer_id]);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <h2>Your Events</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Location</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $event): ?>
        <tr>
          <td><?= $event['title'] ?></td>
          <td><?= $event['date'] ?></td>
          <td><?= $event['location'] ?></td>
          <td>
            <a href="edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="delete_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-danger">Delete</a>
            <!-- Add a "Manage Checklist" button -->
            <a href="manage_checklist.php?event_id=<?= $event['event_id'] ?>" 
               class="btn btn-sm btn-info">Checklist</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>