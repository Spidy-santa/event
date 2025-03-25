<?php
session_start();
include '../includes/db.php'; // Database connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Load PHPMailer (Ensure Composer is installed)

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch event and ticket types
$event_id = $_GET['event_id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE event_id = ?");
$stmt->execute([$event_id]);
$ticket_types = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $eventId = $_POST['event_id'];
    $ticketQty = $_POST['ticket_qty'];

    // Check if the event is a personal event
    $query = "SELECT * FROM events WHERE event_id = '$eventId' AND organizer_id IS NOT NULL";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $query = "INSERT INTO registrations (user_id, event_id, ticket_qty, status) 
                  VALUES ('$userId', '$eventId', '$ticketQty', 'pending')";
        if (mysqli_query($conn, $query)) {
            echo "<p class='success'>You have successfully registered for the event!</p>";
        } else {
            echo "<p class='error'>Failed to register. Please try again.</p>";
        }
    } else {
        echo "<p class='error'>Invalid event or organizer not assigned yet.</p>";
    }

    try {
        $user_id = $_SESSION['user_id'];
        
        // Filter and validate tickets
        $tickets = array_filter($_POST['tickets'], function($t) {
            return $t['qty'] > 0;
        });
        
        // Calculate total
        $total = 0;
        foreach ($tickets as $ticket_id => $ticket) {
            // Get the price from the database to ensure accuracy
            $stmt = $pdo->prepare("SELECT price FROM ticket_types WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $price = $stmt->fetchColumn();
            $total += $price * $ticket['qty'];
        }

        $pdo->beginTransaction();

        // Update ticket availability
        foreach ($tickets as $ticket_id => $ticket) {
            $stmt = $pdo->prepare("UPDATE ticket_types 
                                SET available = available - ? 
                                WHERE id = ? AND available >= ?");
            $stmt->execute([$ticket['qty'], $ticket_id, $ticket['qty']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Ticket no longer available");
            }
        }

        // Generate QR code
        if (!file_exists('../qrcodes')) {
            mkdir('../qrcodes', 0777, true);
        }
        
        $qr_data = "Event: $event_id, User: $user_id, Total: $total";
        $qr_code_path = "../qrcodes/ticket_{$user_id}_{$event_id}.png";
        include '../phpqrcode/qrlib.php';
        
        if (class_exists('QRcode')) {
            QRcode::png($qr_data, $qr_code_path); // Removed the backslash
        } else {
            throw new Exception('QR Code library not properly loaded');
        }

        // Save registration
        $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id, ticket_qty, total_amount, qr_code_path) 
                            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $event_id, array_sum(array_column($tickets, 'qty')), $total, $qr_code_path]);

        $pdo->commit();
        $_SESSION['success'] = "Registration successful!";
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: ../event_details.php?id=$event_id");
        exit();
    }

    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Store password temporarily for email
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Secure password for DB
    $role = $_POST['role'];
    $is_approved = ($role === 'organizer') ? 0 : 1; // Admin must approve organizers

    // Check if email already exists
    $check_email = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->execute([$email]);

    if ($check_email->rowCount() > 0) {
        $_SESSION['error'] = "Email already registered!";
    } else {
        // Insert user into the database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) 
                              VALUES (?, ?, ?, ?, ?)");

        if ($stmt->execute([$name, $email, $hashedPassword, $role, $is_approved])) {
            // Send Email with Login Credentials
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'eventforge777@gmail.com';  // Sender Email
                $mail->Password   = 'hxehhenwdtababfs';  // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Sender and Recipient
                $mail->setFrom('eventforge777@gmail.com', 'Event Management Team');
                $mail->addAddress($email, $name); // Send to registered user

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = "Welcome to Event Management System";
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background-color: #f4f4f4;
                                margin: 0;
                                padding: 0;
                            }
                            .container {
                                width: 100%;
                                max-width: 600px;
                                margin: 20px auto;
                                background: #ffffff;
                                padding: 20px;
                                border-radius: 8px;
                                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                            }
                            .header {
                                background: #007bff;
                                color: #ffffff;
                                text-align: center;
                                padding: 15px;
                                border-radius: 8px 8px 0 0;
                                font-size: 20px;
                            }
                            .content {
                                padding: 20px;
                                color: #333333;
                                font-size: 16px;
                                line-height: 1.6;
                            }
                            .button {
                                display: block;
                                width: 200px;
                                margin: 20px auto;
                                padding: 12px;
                                background: #007bff;
                                color: #ffffff;
                                text-align: center;
                                text-decoration: none;
                                font-size: 16px;
                                border-radius: 5px;
                            }
                            .footer {
                                text-align: center;
                                font-size: 14px;
                                color: #666666;
                                padding: 10px;
                                background: #f4f4f4;
                                border-radius: 0 0 8px 8px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                Welcome to Event Management System 🎉
                            </div>
                            <div class='content'>
                                <p>Hi <strong>$name</strong>,</p>
                                <p>Thank you for registering on our platform! Below are your login credentials:</p>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>Password:</strong> $password</p>
                                <p>Click the button below to log in to your account:</p>
                                <a href='https://yourwebsite.com/login.php' class='button'>Login Now</a>
                                <p>If you did not register on our platform, please ignore this email.</p>
                            </div>
                            <div class='footer'>
                                &copy; " . date('Y') . " Event Management System. All rights reserved.
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                // Send Email
                $mail->send();
                $_SESSION['success'] = "Registration successful! Login details sent to your email.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Registration successful, but email could not be sent. Error: " . $mail->ErrorInfo;
            }

            // Redirect to login page
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register for Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <h2>Register for <?= htmlspecialchars($event['title']) ?></h2>
    
    <form method="POST">
        <div class="ticket-selector">
            <?php foreach($ticket_types as $ticket): ?>
            <div class="ticket-card">
                <h4><?= htmlspecialchars($ticket['name']) ?></h4>
                <p class="price">$<?= number_format($ticket['price'], 2) ?></p>
                <div class="quantity">
                    <button type="button" class="minus">-</button>
                    <input type="number" 
                           name="tickets[<?= $ticket['id'] ?>][qty]" 
                           value="0" 
                           min="0" 
                           max="<?= $ticket['available'] ?>"
                           data-price="<?= $ticket['price'] ?>">
                    <button type="button" class="plus">+</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="total">
            Total: $<span id="total-amount">0.00</span>
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <button class="ical-export" data-event-id="<?= $event['event_id'] ?>">
        <i class="bi bi-calendar-plus"></i> Add to Calendar
    </button>

    <script>
        // Handle quantity buttons
        document.querySelectorAll('.minus, .plus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const value = parseInt(input.value);
                if (this.classList.contains('minus')) {
                    input.value = Math.max(0, value - 1);
                } else {
                    input.value = Math.min(parseInt(input.max), value + 1);
                }
                updateTotal();
            });
        });

        // Calculate total
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.quantity input').forEach(input => {
                total += input.value * input.dataset.price;
            });
            document.getElementById('total-amount').textContent = total.toFixed(2);
        }

        // Calendar export
        document.querySelector('.ical-export').addEventListener('click', function() {
            window.location.href = `export_calendar.php?event_id=${this.dataset.eventId}`;
        });
    </script>
</body>
</html>