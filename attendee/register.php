<html>
<head>
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php
session_start();
include '../includes/db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $is_approved = ($role === 'organizer') ? 0 : 1; // Organizers need approval

    // Check if email already exists
    $check_email = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->execute([$email]);
    if ($check_email->rowCount() > 0) {
        $_SESSION['error'] = "Email already registered!";
    } else {
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) 
                              VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $role, $is_approved])) {
            // Send confirmation email
            $to = $email;
            $subject = "Welcome to Event Management System";
            $message = "
            <html>
            <head>
                <title>Registration Successful</title>
            </head>
            <body>
                <h2>Hello, $name!</h2>
                <p>Thank you for registering on our platform.</p>
                <p><strong>Your Login Details:</strong></p>
                <p>Email: $email</p>
                <p><i>Please keep these details secure.</i></p>
                <p>Best Regards,<br>Event Management Team</p>
            </body>
            </html>
            ";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@yourdomain.com" . "\r\n";

            if (mail($to, $subject, $message, $headers)) {
                $_SESSION['success'] = "Registration successful! Login details sent to your email.";
            } else {
                $_SESSION['error'] = "Registration successful, but email sending failed.";
            }
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Registration failed!";
        }
    }
}
?>
<div class="container mt-5">
    <h2 class="text-center">User Registration</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <form id="registerForm" action="register.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" placeholder="Full Name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm Password" required>
            <div class="text-danger mt-1" id="passwordError"></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-control" name="role" required>
                <option value="attendee">Attendee</option>
                <option value="organizer">Organizer</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<script>
    $(document).ready(function(){
        $("#registerForm").submit(function(event){
            var password = $("#password").val();
            var confirmPassword = $("#confirmPassword").val();
            
            if (password !== confirmPassword) {
                event.preventDefault();
                $("#passwordError").text("Passwords do not match!");
            } else {
                $("#passwordError").text("");
            }
        });
    });
</script>
</body>
</html>