<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['user_id'])) {
    http_response_code(400);
    echo "User ID is required";
    exit();
}

$user_id = $_POST['user_id'];
$action = $_POST['action'] ?? 'approve';

try {
    if ($action === 'approve') {
        // First check if user exists and is not already approved
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'organizer' AND is_approved = 0");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Invalid user or already approved');
        }

        // Update user approval status
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ?");
        $result = $stmt->execute([$user_id]);
        
        if (!$result) {
            throw new Exception('Failed to update user status');
        }

        // Send email with credentials
        $to = $user['email'];
        $subject = 'Your Organizer Account Has Been Approved';
        $message = "Your organizer account has been approved.\n\n";
        $message .= "You can now login using your registered email address.\n";
        $message .= "Email: " . $user['email'] . "\n\n";
        $message .= "Please use your existing password to login.";
        $headers = 'From: noreply@eventmanagement.com';

        mail($to, $subject, $message, $headers);
        
        $_SESSION['success'] = "Organizer approved successfully.";

    } elseif ($action === 'reject') {
        // Get user email before deletion
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Delete the rejected organizer
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$user_id]);
        
        // Send rejection email
        $to = $user['email'];
        $subject = 'Organizer Application Status';
        $message = "We regret to inform you that your organizer application has been rejected.\n\n";
        $message .= "If you have any questions, please contact our support team.";
        $headers = 'From: noreply@eventmanagement.com';
        
        mail($to, $subject, $message, $headers);
        
        $_SESSION['success'] = "Organizer application rejected.";

    } elseif ($action === 'remove') {
        // Get user email before deletion
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Delete the organizer
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$user_id]);
        
        // Send removal notification
        $to = $user['email'];
        $subject = 'Organizer Account Removed';
        $message = "Your organizer account has been removed from our system.\n\n";
        $message .= "If you believe this is an error, please contact our support team.";
        $headers = 'From: noreply@eventmanagement.com';
        
        mail($to, $subject, $message, $headers);
        
        $_SESSION['success'] = "Organizer removed successfully.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header("Location: manage_organizers.php");
exit();
?>