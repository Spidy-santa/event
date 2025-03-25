<?php
session_start();
require '../includes/db.php';

// Restrict to admins
if ($_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch events
$stmt = $pdo->query("SELECT * FROM events");
$events = $stmt->fetchAll();

// Delete Event
if (isset($_GET['delete_event'])) {
  $event_id = $_GET['delete_event'];
  $pdo->prepare("DELETE FROM events WHERE event_id = ?")->execute([$event_id]);
  header("Location: manage_events.php");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <h2>Manage Events</h2>
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
      <?php foreach($events as $event): ?>
      <tr>
        <td><?= $event['title'] ?></td>
        <td><?= $event['date'] ?></td>
        <td><?= $event['location'] ?></td>
        <td>
          <a href="manage_events.php?delete_event=<?= $event['event_id'] ?>" class="btn btn-danger">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>