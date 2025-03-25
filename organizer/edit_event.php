<?php
session_start();
include '../includes/db.php';

// Check if organizer owns the event
$event_id = $_GET['id'];
$organizer_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND organizer_id = ?");
$stmt->execute([$event_id, $organizer_id]);
$event = $stmt->fetch();

if (!$event) {
  $_SESSION['error'] = "Event not found or unauthorized!";
  header("Location: manage_events.php");
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = htmlspecialchars($_POST['title']);
  $date = $_POST['date'];
  $location = htmlspecialchars($_POST['location']);
  $category = $_POST['category'];
  $reg_start_date = $_POST['reg_start_date'];
  $reg_end_date = $_POST['reg_end_date'];
  $total_tickets = intval($_POST['total_tickets']);
  $ticket_price = floatval($_POST['ticket_price']);

  // Handle image upload
  $image_path = $event['image_path'];
  if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../assets/images/events/';
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
    $image_path = $upload_dir . uniqid('event_', true) . '.' . $file_extension;
    move_uploaded_file($_FILES['event_image']['tmp_name'], $image_path);
  }

  // Capture dynamic fields
  $details = [];
  foreach ($_POST as $key => $value) {
    if ($key !== 'title' && $key !== 'date' && $key !== 'location' && $key !== 'category') {
      $details[$key] = htmlspecialchars($value);
    }
  }
  $details_json = json_encode($details);

  // Update database
  $stmt = $pdo->prepare("UPDATE events SET title = ?, date = ?, location = ?, category = ?, 
                        details = ?, reg_start_date = ?, reg_end_date = ?, 
                        total_tickets = ?, ticket_price = ?, image_path = ? 
                        WHERE event_id = ? AND organizer_id = ?");
  if ($stmt->execute([$title, $date, $location, $category, $details_json, 
                      $reg_start_date, $reg_end_date, $total_tickets, 
                      $ticket_price, $image_path, $event_id, $organizer_id])) {
    $_SESSION['success'] = "Event updated successfully!";
    header("Location: manage_events.php");
    exit();
  } else {
    $_SESSION['error'] = "Failed to update event.";
  }
}

// Fetch existing details
$details = json_decode($event['details'], true);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Event</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container mt-5">
  <h2>Edit Event</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <input type="text" name="title" class="form-control" placeholder="Event Title" value="<?= htmlspecialchars($event['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Date</label>
      <input type="date" name="date" class="form-control" value="<?= $event['date'] ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Registration Period</label>
      <div class="row">
        <div class="col">
          <input type="date" name="reg_start_date" class="form-control" value="<?= $event['reg_start_date'] ?>" required>
        </div>
        <div class="col">
          <input type="date" name="reg_end_date" class="form-control" value="<?= $event['reg_end_date'] ?>" required>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <input type="text" name="location" class="form-control" placeholder="Location" value="<?= htmlspecialchars($event['location']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Image</label>
      <input type="file" name="event_image" class="form-control" accept="image/*">
      <?php if ($event['image_path']): ?>
        <img src="<?= $event['image_path'] ?>" alt="Current Event Image" class="mt-2" style="max-width: 200px;">
      <?php endif; ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Ticket Information</label>
      <div class="row">
        <div class="col">
          <input type="number" name="total_tickets" class="form-control" placeholder="Total Tickets Available" value="<?= $event['total_tickets'] ?>" required min="1">
        </div>
        <div class="col">
          <input type="number" name="ticket_price" class="form-control" placeholder="Ticket Price" value="<?= $event['ticket_price'] ?>" required min="0" step="0.01">
        </div>
      </div>
    </div>
    <div class="mb-3">
      <select name="category" id="event-category" class="form-control" required>
        <option value="">Select Event Category</option>
        <option value="wedding" <?= $event['category'] === 'wedding' ? 'selected' : '' ?>>Wedding</option>
        <option value="conference" <?= $event['category'] === 'conference' ? 'selected' : '' ?>>Conference</option>
        <option value="liveconcert" <?= $event['category'] === 'liveconcert' ? 'selected' : '' ?>>Live Concert</option>
        <option value="workshop" <?= $event['category'] === 'workshop' ? 'selected' : '' ?>>Workshop</option>
      </select>
    </div>
    <div id="category-options" class="mb-3"></div>
    <div class="mb-3">
      <label class="form-label">Total Price</label>
      <input type="text" id="total-price" class="form-control" readonly>
      <input type="hidden" name="total_price" id="total-price-input">
    </div>
    <div id="dynamic-fields" class="mb-3">
      <!-- Dynamic fields load here via AJAX -->
    </div>
    
    <div id="budget-items" class="mb-3">
      <h4>Budget Items</h4>
      <!-- Budget items will load here -->
    </div>
    
    <button type="submit" class="btn btn-primary">Update Event</button>
  </form>

  <script>
  // Load dynamic fields when category changes
  $("#event-category").change(function() {
    const category = $(this).val();
    $.ajax({
      url: "get_category_options.php",
      data: { category: category },
      success: function(data) {
        let html = '';
        const options = data.options;
        
        for (const [key, option] of Object.entries(options)) {
          html += `<div class="mb-3">`;
          html += `<label class="form-label">${option.label}</label>`;
          
          if (option.type === 'select') {
            html += `<select name="${key}" class="form-control option-select" data-option="${key}">`;
            html += `<option value="">Select ${option.label}</option>`;
            option.options.forEach(opt => {
              html += `<option value="${opt.value}" data-price="${opt.price}">${opt.label}</option>`;
            });
            html += '</select>';
          } else if (option.type === 'checkbox') {
            option.options.forEach(opt => {
              html += `<div class="form-check">`;
              html += `<input class="form-check-input option-checkbox" type="checkbox" name="${key}[]" `;
              html += `value="${opt.value}" data-price="${opt.price}" data-option="${key}">`;
              html += `<label class="form-check-label">${opt.label}</label>`;
              html += `</div>`;
            });
          }
          html += '</div>';
        }
        
        $('#category-options').html(html);
        
        // Set existing values if any
        const details = <?= json_encode($details) ?>;
        if (details) {
          for (const [key, value] of Object.entries(details)) {
            if (Array.isArray(value)) {
              value.forEach(v => {
                $(`input[name="${key}[]"][value="${v}"]`).prop('checked', true);
              });
            } else {
              $(`select[name="${key}"]`).val(value);
            }
          }
        }
      }
    });
  });
  
  // Trigger change event to load current category options
  $(document).ready(function() {
    $("#event-category").trigger('change');
  });
  </script>
</body>
</html>