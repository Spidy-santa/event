<?php
session_start();
require '../vendor/autoload.php';
include '../includes/db.php';

\Stripe\Stripe::setApiKey('sk_test_XXXXXXXXXXXXXXXXXXXXXXXX'); // Replace with your test key

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$event_id = $_GET['event_id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
  $_SESSION['error'] = "Event not found!";
  header("Location: ../index.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $amount = $event['ticket_price'] * 100; // Convert to cents
  $currency = 'usd';

  try {
    $paymentIntent = \Stripe\PaymentIntent::create([
      'amount' => $amount,
      'currency' => $currency,
    ]);

    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
    exit();
  } catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Payment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="container mt-5">
  <h2>Payment for <?= $event['title'] ?></h2>
  <p>Total: $<?= $event['ticket_price'] ?></p>
  <form id="payment-form">
    <div id="card-element" class="mb-3"></div>
    <button id="submit" class="btn btn-primary">Pay Now</button>
  </form>

  <script>
  const stripe = Stripe('pk_test_XXXXXXXXXXXXXXXXXXXXXXXX'); // Replace with your publishable key
  const elements = stripe.elements();
  const cardElement = elements.create('card');
  cardElement.mount('#card-element');

  const form = document.getElementById('payment-form');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const { error, paymentMethod } = await stripe.createPaymentMethod({
      type: 'card',
      card: cardElement,
    });

    if (error) {
      alert(error.message);
    } else {
      fetch('payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          paymentMethodId: paymentMethod.id,
          eventId: <?= $event_id ?>
        }),
      }).then(response => response.json())
        .then(data => {
          if (data.clientSecret) {
            alert('Payment successful!');
            window.location.href = 'dashboard.php';
          } else {
            alert('Payment failed: ' + data.error);
          }
        });
    }
  });
  </script>
</body>
</html>