<?php
session_start();
include '../includes/db.php';

$event_id = $_POST['event_id'];
$tasks = $_POST['tasks'];
$completed = $_POST['completed'] ?? [];

// Delete existing checklist
$stmt = $pdo->prepare("DELETE FROM checklists WHERE event_id = ?");
$stmt->execute([$event_id]);

// Insert new checklist
foreach ($tasks as $index => $task) {
  $is_completed = isset($completed[$index]) ? 1 : 0;
  $stmt = $pdo->prepare("INSERT INTO checklists (event_id, task, is_completed)
                        VALUES (?, ?, ?)");
  $stmt->execute([$event_id, $task, $is_completed]);
}

$_SESSION['success'] = "Checklist updated!";
header("Location: manage_checklist.php?event_id=$event_id");
?>