<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'organizer'");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user) {
    if ($user['is_approved'] == 0) {
      $_SESSION['error'] = "Your account is pending admin approval.";
    } elseif (password_verify($password, $user['password'])) {
      // Set session variables after successful authentication
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['name'] = $user['name'];
      
      // Redirect to organizer dashboard
      header("Location: dashboard.php");
      exit();
    } else {
      $_SESSION['error'] = "Invalid email or password!";
    }
  } else {
    $_SESSION['error'] = "Invalid email or password!";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Organizer Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <h2>Organizer Login</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
      <a href="register.php" class="btn btn-link">Register as Organizer</a>
    </form>
  </div>
</body>
</html>