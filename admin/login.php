<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Hardcoded admin credentials
  if ($email === 'admin' && $password === 'admin123') {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    header("Location: dashboard.php");
    exit();
  }

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    if ($user['role'] === 'organizer' && $user['is_approved'] === 0) {
      $_SESSION['error'] = "Your account is pending admin approval.";
    } else {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['role'] = $user['role'];
      
      // Redirect based on role
      if ($user['role'] === 'admin') {
        header("Location: dashboard.php");
      } elseif ($user['role'] === 'organizer') {
        header("Location: organizer/dashboard.php");
      } else {
        header("Location: attendee/dashboard.php");
      }
      exit();
    }
  } else {
    $_SESSION['error'] = "Invalid email or password!";
    header("Location: login.php");
    exit();
  }
}
?>
<form action="login.php" method="POST">
  <input type="text" name="email" placeholder="Admin Username" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Login</button>
</form>