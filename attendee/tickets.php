<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// NFT Ticket Support
function generate_nft_ticket($ticket_id) {
  $metadata = [
    'name' => "Event Ticket #$ticket_id",
    'description' => "Digital ticket for ".$event['title'],
    'image' => generate_nft_image($ticket_id),
    'attributes' => [
      ['trait_type' => 'Event Date', 'value' => $event['date']],
      ['trait_type' => 'Venue', 'value' => $event['location']]
    ]
  ];
  
  $ipfs = upload_to_ipfs(json_encode($metadata));
  mint_nft($_SESSION['user_wallet'], $ipfs);
  return $ipfs;
}

// Get user's tickets
$stmt = $pdo->prepare("SELECT * FROM tickets 
                      WHERE user_id = ? ORDER BY event_date DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<!-- Ticket Gallery -->
<div class="ticket-gallery">
  <?php foreach($tickets as $ticket): ?>
  <div class="ticket-card <?= $ticket['nft_id'] ? 'nft-ticket' : '' ?>">
    <div class="qr-code">
      <img src="<?= $ticket['qr_code'] ?>" alt="Ticket QR Code">
    </div>
    <div class="ticket-info">
      <h4><?= $ticket['event_title'] ?></h4>
      <p><?= date('M j, Y', strtotime($ticket['event_date'])) ?></p>
      <?php if($ticket['nft_id']): ?>
      <a href="https://opensea.io/assets/<?= $ticket['nft_id'] ?>" 
         class="nft-badge" target="_blank">
        <i class="bi bi-coin"></i> View NFT
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>