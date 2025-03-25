<?php
session_start();
include '../includes/db.php';

$event_id = $_GET['id'];
$organizer_id = $_SESSION['user_id'];

// Delete only if organizer owns the event
$stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ? AND organizer_id = ?");
if ($stmt->execute([$event_id, $organizer_id])) {
  $_SESSION['success'] = "Event deleted!";
} else {
  $_SESSION['error'] = "Deletion failed!";
}
header("Location: manage_events.php");
?>