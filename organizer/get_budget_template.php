<?php
include '../includes/db.php';

$category = $_GET['category'];
$stmt = $pdo->prepare("SELECT * FROM budget_templates WHERE category = ?");
$stmt->execute([$category]);
$template = $stmt->fetchAll();

echo json_encode($template);
?>