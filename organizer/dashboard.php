<?php
session_start();
include '../includes/db.php';

// Restrict access to approved organizers
if ($_SESSION['role'] !== 'organizer' || !isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$organizer_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Organizer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .dashboard-card { transition: transform 0.2s; }
    .dashboard-card:hover { transform: translateY(-5px); }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">Organizer Dashboard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              Events
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="create_event.php">Create Event</a></li>
              <li><a class="dropdown-item" href="manage_events.php">Manage Events</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_checklist.php">Checklists</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_budget.php">Budget</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="registrations.php">Registrations</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mt-5">
    <h2 class="mb-4">Welcome, <?= $_SESSION['name'] ?></h2>
    
    <!-- Quick Stats Cards -->
    <div class="row">
      <!-- Total Events -->
      <div class="col-md-4 mb-4">
        <div class="card dashboard-card bg-primary text-white">
          <div class="card-body">
            <h5 class="card-title">Total Events</h5>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
            $stmt->execute([$organizer_id]);
            $total_events = $stmt->fetchColumn();
            ?>
            <h1><?= $total_events ?></h1>
            <a href="manage_events.php" class="text-white">View Events →</a>
          </div>
        </div>
      </div>

      <!-- Upcoming Events -->
      <div class="col-md-4 mb-4">
        <div class="card dashboard-card bg-success text-white">
          <div class="card-body">
            <h5 class="card-title">Upcoming Events</h5>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM events 
                                  WHERE organizer_id = ? AND date >= CURDATE()");
            $stmt->execute([$organizer_id]);
            $upcoming_events = $stmt->fetchColumn();
            ?>
            <h1><?= $upcoming_events ?></h1>
            <a href="manage_events.php?filter=upcoming" class="text-white">View Upcoming →</a>
          </div>
        </div>
      </div>

      <!-- Total Registrations -->
      <div class="col-md-4 mb-4">
        <div class="card dashboard-card bg-info text-white">
          <div class="card-body">
            <h5 class="card-title">Total Registrations</h5>
            <?php
            $stmt = $pdo->prepare("SELECT SUM(ticket_qty) FROM registrations 
                                  WHERE event_id IN 
                                    (SELECT event_id FROM events WHERE organizer_id = ?)");
            $stmt->execute([$organizer_id]);
            $total_registrations = $stmt->fetchColumn() ?? 0;
            ?>
            <h1><?= $total_registrations ?></h1>
            <a href="registrations.php" class="text-white">View Registrations →</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Events Table -->
    <div class="card">
      <div class="card-header">
        <h4>Recent Events</h4>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Event Title</th>
              <th>Date</th>
              <th>Registrations</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM events 
                                  WHERE organizer_id = ? 
                                  ORDER BY date DESC LIMIT 5");
            $stmt->execute([$organizer_id]);
            $events = $stmt->fetchAll();

            foreach ($events as $event):
              // Get registrations count for each event
              $stmt = $pdo->prepare("SELECT SUM(ticket_qty) FROM registrations 
                                    WHERE event_id = ?");
              $stmt->execute([$event['event_id']]);
              $registrations = $stmt->fetchColumn() ?? 0;
            ?>
              <tr>
                <td><?= $event['title'] ?></td>
                <td><?= date('M d, Y', strtotime($event['date'])) ?></td>
                <td><?= $registrations ?></td>
                <td>
                  <?= (strtotime($event['date']) >= time()) ? 
                      '<span class="badge bg-success">Upcoming</span>' : 
                      '<span class="badge bg-secondary">Completed</span>' ?>
                </td>
                <td>
                  <a href="edit_event.php?id=<?= $event['event_id'] ?>" 
                     class="btn btn-sm btn-warning">Edit</a>
                  <a href="event_details.php?id=<?= $event['event_id'] ?>" 
                     class="btn btn-sm btn-info">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>