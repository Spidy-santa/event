<?php
// Event Reviews System
if ($_POST['review']) {
  $stmt = $pdo->prepare("INSERT INTO reviews 
                        (user_id, event_id, rating, comment) 
                        VALUES (?, ?, ?, ?)");
  $stmt->execute([
    $_SESSION['user_id'],
    $_POST['event_id'],
    $_POST['rating'],
    htmlspecialchars($_POST['comment'])
  ]);
  
  update_event_rating($_POST['event_id']);
}
?>

<!-- Review Form -->
<div class="review-form">
  <div class="rating-stars">
    <?php for($i=1; $i<=5; $i++): ?>
    <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>">
    <label for="star<?= $i ?>"></label>
    <?php endfor; ?>
  </div>
  <textarea name="comment" placeholder="Share your experience..."></textarea>
  <button class="btn-post-review">Post Review</button>
</div>

<!-- Social Sharing -->
<div class="social-sharing">
  <button class="share-facebook" data-url="<?= $event_url ?>">
    <i class="bi bi-facebook"></i>
  </button>
  <button class="share-twitter" data-url="<?= $event_url ?>" 
          data-text="Check out this event!">
    <i class="bi bi-twitter"></i>
  </button>
</div>