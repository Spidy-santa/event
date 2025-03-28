<?php
session_start();
include '../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

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

        // After successful registration, before commit
        // Get user email
        $stmt = $pdo->prepare("SELECT email, name FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Send confirmation email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'eventforge777@gmail.com';
            $mail->Password   = 'hxehhenwdtababfs';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('eventforge777@gmail.com', 'Event Management Team');
            $mail->addAddress($user['email'], $user['name']);
            $mail->isHTML(true);
            $mail->Subject = "Event Registration Confirmation - {$event['title']}";

            // Email body with ticket details
            $mail->Body = "
                <h2>Registration Confirmation</h2>
                <p>Dear {$user['name']},</p>
                <p>Your registration for {$event['title']} is confirmed.</p>
                <p>Total Amount: $${total}</p>
                <p>Your QR code ticket is attached.</p>";

            $mail->addAttachment($qr_code_path, 'event_ticket.png');
            $mail->send();
        } catch (Exception $e) {
            // Log email error but continue with registration
            error_log("Email sending failed: {$mail->ErrorInfo}");
        }

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