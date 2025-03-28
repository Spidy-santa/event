<?php
session_start();
include '../includes/db.php';

// Enhance security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

        if (!$user_id || !$action) {
            throw new Exception('Invalid input parameters');
        }

        // Verify user exists and is an organizer
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$user_id]);
        $organizer = $stmt->fetch();

        if (!$organizer) {
            throw new Exception('Invalid organizer');
        }

        switch ($action) {
            case 'approve':
                // Update approval status and send welcome email
                $stmt = $pdo->prepare("UPDATE users SET is_approved = 1, is_active = 1 WHERE user_id = ?");
                $result = $stmt->execute([$user_id]);
                
                // Send approval email
                sendApprovalEmail($organizer['email'], $organizer['name']);
                $response = ['status' => 'success', 'message' => 'Organizer approved successfully'];
                break;

            case 'reject':
                // Store rejection reason if provided
                $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
                
                // Delete the organizer account
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Send rejection email
                sendRejectionEmail($organizer['email'], $organizer['name'], $reason);
                $response = ['status' => 'success', 'message' => 'Organizer rejected'];
                break;

            default:
                throw new Exception('Invalid action');
        }

        $pdo->commit();
        $_SESSION['success'] = $response['message'];
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    header('Location: manage_organizers.php');
    exit;
}

// Helper functions for emails
function sendApprovalEmail($email, $name) {
    $subject = "Organizer Account Approved";
    $message = "Dear $name,\n\n"
             . "Your organizer account has been approved! You can now:\n"
             . "- Create and manage events\n"
             . "- Access the organizer dashboard\n"
             . "- View analytics for your events\n\n"
             . "Login to your account to get started.\n\n"
             . "Best regards,\nEvent Management Team";
    
    mail($email, $subject, $message, "From: noreply@eventmanagement.com");
}

function sendRejectionEmail($email, $name, $reason = '') {
    $subject = "Organizer Application Status";
    $message = "Dear $name,\n\n"
             . "We regret to inform you that your organizer application was not approved.";
    
    if ($reason) {
        $message .= "\n\nReason: $reason";
    }
    
    $message .= "\n\nIf you have any questions, please contact our support team.\n\n"
              . "Best regards,\nEvent Management Team";
    
    mail($email, $subject, $message, "From: noreply@eventmanagement.com");
}
?>