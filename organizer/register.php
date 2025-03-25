<!DOCTYPE html>
<html>
<head>
  <title>Organizer Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h3>Organizer Registration</h3>
          </div>
          <div class="card-body">
            <?php
session_start();
include '../includes/db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = htmlspecialchars($_POST['name']);
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = 'organizer'; // Force role to be organizer
  $is_approved = 0; // Organizers need approval

  // Insert into database
  $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) 
                        VALUES (?, ?, ?, ?, ?)");
  if ($stmt->execute([$name, $email, $password, $role, $is_approved])) {
    $_SESSION['success'] = "Registration successful! Your account is pending admin approval.";
    header("Location: login.php");
    exit();
  } else {
    $_SESSION['error'] = "Registration failed!";
  }
}
?>
            <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="name" class="form-label">Organization Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Business Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>
              <div class="mb-3">
                <label for="phone" class="form-label">Contact Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" required>
              </div>
              <div class="mb-3">
                <label for="address" class="form-label">Business Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
              </div>
              <div class="mb-3">
                <label for="description" class="form-label">Organization Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
              </div>
              <div class="mb-3">
                <label for="logo" class="form-label">Organization Logo</label>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/*" required>
              </div>
              <div class="mb-3">
                <label for="website" class="form-label">Website (Optional)</label>
                <input type="url" class="form-control" id="website" name="website">
              </div>
              <div class="mb-3">
                <label for="social_media" class="form-label">Social Media Links (Optional)</label>
                <input type="text" class="form-control mb-2" placeholder="Facebook" name="social_media[facebook]">
                <input type="text" class="form-control mb-2" placeholder="Twitter" name="social_media[twitter]">
                <input type="text" class="form-control" placeholder="Instagram" name="social_media[instagram]">
              </div>
              <button type="submit" class="btn btn-primary">Register as Organizer</button>
            </form>
            <p class="text-center mt-3">
              Already have an account? <a href="login.php">Login here</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>