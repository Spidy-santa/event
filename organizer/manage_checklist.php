<?php
session_start();
include '../includes/db.php';

// Verify organizer owns the event
$event_id = $_GET['event_id'];
$organizer_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND organizer_id = ?");
$stmt->execute([$event_id, $organizer_id]);
$event = $stmt->fetch();

if (!$event) {
  $_SESSION['error'] = "Unauthorized access!";
  header("Location: manage_events.php");
  exit();
}

// Fetch existing checklist
$stmt = $pdo->prepare("SELECT * FROM checklists WHERE event_id = ?");
$stmt->execute([$event_id]);
$checklist = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manage Checklist</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container mt-5">
  <h2>Checklist for <?= $event['title'] ?></h2>
  <form method="POST" action="save_checklist.php">
    <input type="hidden" name="event_id" value="<?= $event_id ?>">
    <div id="checklist-items">
      <?php foreach ($checklist as $item): ?>
        <div class="mb-3">
          <input type="text" name="tasks[]" value="<?= $item['task'] ?>" class="form-control">
          <input type="checkbox" name="completed[]" <?= $item['is_completed'] ? 'checked' : '' ?>>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-secondary" onclick="addTask()">Add Task</button>
    <button type="submit" class="btn btn-primary">Save</button>
  </form>

  <script>
  function addTask() {
    const html = `
      <div class="mb-3">
        <input type="text" name="tasks[]" class="form-control" placeholder="New Task">
        <input type="checkbox" name="completed[]">
      </div>
    `;
    $("#checklist-items").append(html);
  }
  </script>
</body>
</html>