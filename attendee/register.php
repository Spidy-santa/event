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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Temporary store for email
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Validate role
    if (!in_array($role, ['attendee', 'organizer'])) {
        $_SESSION['error'] = "Invalid role selected!";
        header("Location: register.php");
        exit();
    }
    
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
        if ($stmt->execute([$name, $email, $hashedPassword, $role, $is_approved])) {
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'eventforge777@gmail.com';
                $mail->Password   = 'hxehhenwdtababfs';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Email setup
                $mail->setFrom('eventforge777@gmail.com', 'Event Management Team');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = "Welcome to Event Management System";
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { max-width: 600px; margin: 20px auto; padding: 20px; }
                            .header { background: #007bff; color: #ffffff; padding: 15px; text-align: center; }
                            .content { padding: 20px; }
                            .footer { text-align: center; padding: 10px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>Welcome to Event Management System 🎉</div>
                            <div class='content'>
                                <p>Hi <strong>$name</strong>,</p>
                                <p>Thank you for registering! Your login credentials:</p>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>Password:</strong> $password</p>
                            </div>
                            <div class='footer'>&copy; " . date('Y') . " Event Management System</div>
                        </div>
                    </body>
                    </html>";

                $mail->send();
                $_SESSION['success'] = "Registration successful! Login details sent to your email.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Registration successful, but email could not be sent.";
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
                <option value="organizer">Event Organizer</option>
            </select>
            <small class="form-text text-muted">
                Note: Organizer accounts require admin approval before activation.
            </small>
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