<?php
session_start();
require '../includes/db.php';

// Restrict to admins
if ($_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch data for charts
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$user_roles = $stmt->fetchAll();

$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM events GROUP BY category");
$event_categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Analytics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="container mt-5">
  <h2>Analytics</h2>
  
  <!-- Charts -->
  <div class="row">
    <div class="col-md-6">
      <canvas id="userRolesChart"></canvas>
    </div>
    <div class="col-md-6">
      <canvas id="eventCategoriesChart"></canvas>
    </div>
  </div>

  <script>
  // User Roles Chart
  new Chart(document.getElementById('userRolesChart'), {
    type: 'pie',
    data: {
      labels: <?= json_encode(array_column($user_roles, 'role')) ?>,
      datasets: [{
        data: <?= json_encode(array_column($user_roles, 'count')) ?>,
        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
      }]
    }
  });

  // Event Categories Chart
  new Chart(document.getElementById('eventCategoriesChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($event_categories, 'category')) ?>,
      datasets: [{
        label: 'Events by Category',
        data: <?= json_encode(array_column($event_categories, 'count')) ?>,
        backgroundColor: '#4BC0C0'
      }]
    }
  });
  </script>
</body>
</html>