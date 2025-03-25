<?php
session_start();
require '../includes/db.php';

// Check if the connection is established
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Restrict to attendees
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendee') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the event request form
    $userId = $_SESSION['user_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $eventType = $_POST['event_type'];
    $eventDetails = $_POST['event_details'];
    $preferredDate = $_POST['preferred_date'];
    $location = $_POST['location'];
    $totalAttendees = $_POST['total_attendees'];
    
    // Serialize all category-specific selections and pricing
    $selectedOptions = [];
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        foreach ($_POST['options'] as $key => $value) {
            if ($value == 'on') {
                $selectedOptions[$key] = [
                    'price' => $_POST['price_' . $key],
                    'is_per_person' => isset($_POST['is_per_person_' . $key]) ? true : false,
                    'total_price' => $_POST['total_' . $key]
                ];
            }
        }
    }
    
    $optionsJson = json_encode($selectedOptions);
    $totalPrice = $_POST['total_price'];
    
    // Always assign to admin
    $assignTo = 'admin';

    // Updated query to match existing table structure
    $query = "INSERT INTO event_requests 
              (user_id, name, address, contact, event_type, event_details, preferred_date, location, 
               total_attendees, assigned_by, event_budget) 
              VALUES 
              ('$userId', '$name', '$address', '$contact', '$eventType', '$eventDetails', '$preferredDate', 
               '$location', '$totalAttendees', '$assignTo', '$totalPrice')";
    
    if (mysqli_query($conn, $query)) {
        echo "<p class='success'>Your event request has been submitted successfully!</p>";
    } else {
        echo "<p class='error'>Failed to submit your request. Please try again: " . mysqli_error($conn) . "</p>";
    }
}

// Fetch any service prices from the database (in a real system, these would be retrieved from a database)
// For now using fixed values
$serviceDetails = [
    // Wedding services
    'venue_wedding' => ['name' => 'Venue Rental', 'price' => 50000, 'is_per_person' => false],
    'decoration_wedding' => ['name' => 'Decoration/Setting', 'price' => 25000, 'is_per_person' => false],
    'food_veg' => ['name' => 'Food & Beverage (Vegetarian)', 'price' => 700, 'is_per_person' => true],
    'food_nonveg' => ['name' => 'Food & Beverage (Non-Vegetarian)', 'price' => 900, 'is_per_person' => true],
    'photography' => ['name' => 'Photography Package', 'price' => 15000, 'is_per_person' => false],
    'entertainment_wedding' => ['name' => 'Entertainment', 'price' => 20000, 'is_per_person' => false],
    
    // Birthday services
    'venue_birthday' => ['name' => 'Venue/Setting', 'price' => 20000, 'is_per_person' => false],
    'cake' => ['name' => 'Cake', 'price' => 3000, 'is_per_person' => false],
    'decoration_birthday' => ['name' => 'Decoration', 'price' => 10000, 'is_per_person' => false],
    'entertainment_birthday' => ['name' => 'Entertainment/DJ', 'price' => 15000, 'is_per_person' => false],
    'food_birthday' => ['name' => 'Food', 'price' => 600, 'is_per_person' => true],
    
    // Conference services
    'venue_conference' => ['name' => 'Venue Rental', 'price' => 40000, 'is_per_person' => false],
    'av_equipment' => ['name' => 'Audio/Visual Equipment', 'price' => 25000, 'is_per_person' => false],
    'catering_conference' => ['name' => 'Catering', 'price' => 750, 'is_per_person' => true],
    'speaker_fee' => ['name' => 'Speaker/Program Fee', 'price' => 30000, 'is_per_person' => false],
    
    // Workshop services
    'venue_workshop' => ['name' => 'Venue', 'price' => 20000, 'is_per_person' => false],
    'materials' => ['name' => 'Materials/Equipment', 'price' => 400, 'is_per_person' => true],
    'instructor' => ['name' => 'Instructor/Facilitator', 'price' => 25000, 'is_per_person' => false],
    'refreshments' => ['name' => 'Refreshments', 'price' => 300, 'is_per_person' => true]
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Personal Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .category-fields {
            display: none;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .service-option {
            border: 1px solid #e9e9e9;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .price-summary {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .tooltip-icon {
            color: #007bff;
            cursor: pointer;
            margin-left: 5px;
        }
        [data-bs-toggle="tooltip"] {
            cursor: pointer;
        }
        .fixed-price {
            font-weight: bold;
            color: #198754;
        }
        .per-person-price {
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body class="container mt-5">
    <h2>Request Personal Event</h2>
    <form method="POST" action="" id="eventRequestForm">
        <div class="mb-3">
            <label for="name" class="form-label">Your Name:</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address:</label>
            <textarea id="address" name="address" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label for="contact" class="form-label">Contact Number:</label>
            <input type="text" id="contact" name="contact" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="event_type" class="form-label">Event Type:</label>
            <select id="event_type" name="event_type" class="form-select" required>
                <option value="">Select Event Type</option>
                <option value="wedding">Wedding</option>
                <option value="birthday">Birthday Party</option>
                <option value="conference">Conference</option>
                <option value="workshop">Workshop</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="total_attendees" class="form-label">Total Number of Attendees:</label>
            <input type="number" id="total_attendees" name="total_attendees" min="1" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="event_details" class="form-label">Event Details:</label>
            <textarea id="event_details" name="event_details" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label for="preferred_date" class="form-label">Preferred Date:</label>
            <input type="date" id="preferred_date" name="preferred_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location:</label>
            <input type="text" id="location" name="location" class="form-control" required>
        </div>
        
        <!-- Category-specific fields will be loaded here -->
        <div id="category-fields-container">
            <!-- Wedding Fields -->
            <div id="wedding-fields" class="category-fields">
                <h4>Wedding Services</h4>
                
                <?php
                // Wedding services
                $weddingServices = ['venue_wedding', 'decoration_wedding', 'food_veg', 'food_nonveg', 'photography', 'entertainment_wedding'];
                
                foreach ($weddingServices as $serviceId) {
                    $service = $serviceDetails[$serviceId];
                    $isPerPerson = $service['is_per_person'];
                    $priceLabel = $isPerPerson ? 'Price per person (₹): ' : 'Fixed Price (₹): ';
                    $priceClass = $isPerPerson ? 'per-person-price' : 'fixed-price';
                    
                    echo '<div class="service-option">
                            <div class="form-check">
                                <input type="checkbox" id="' . $serviceId . '" name="options[' . $serviceId . ']" class="form-check-input service-checkbox" 
                                       data-is-per-person="' . ($isPerPerson ? 'true' : 'false') . '">
                                <label for="' . $serviceId . '" class="form-check-label">' . $service['name'] . '</label>
                            </div>
                            <div class="mt-2">
                                <label><span class="' . $priceClass . '">' . $priceLabel . 
                                      '<span class="price-display">' . number_format($service['price']) . '</span></span></label>
                                <input type="hidden" name="price_' . $serviceId . '" id="price_' . $serviceId . '" value="' . $service['price'] . '">
                                <input type="hidden" name="is_per_person_' . $serviceId . '" value="' . ($isPerPerson ? '1' : '0') . '">
                                <div class="mt-1">Total: ₹<span id="total_' . $serviceId . '">0</span></div>
                                <input type="hidden" name="total_' . $serviceId . '" value="0">
                            </div>
                          </div>';
                }
                ?>
            </div>
            
            <!-- Birthday Party Fields -->
            <div id="birthday-fields" class="category-fields">
                <h4>Birthday Party Services</h4>
                
                <?php
                // Birthday services
                $birthdayServices = ['venue_birthday', 'cake', 'decoration_birthday', 'entertainment_birthday', 'food_birthday'];
                
                foreach ($birthdayServices as $serviceId) {
                    $service = $serviceDetails[$serviceId];
                    $isPerPerson = $service['is_per_person'];
                    $priceLabel = $isPerPerson ? 'Price per person (₹): ' : 'Fixed Price (₹): ';
                    $priceClass = $isPerPerson ? 'per-person-price' : 'fixed-price';
                    
                    echo '<div class="service-option">
                            <div class="form-check">
                                <input type="checkbox" id="' . $serviceId . '" name="options[' . $serviceId . ']" class="form-check-input service-checkbox" 
                                       data-is-per-person="' . ($isPerPerson ? 'true' : 'false') . '">
                                <label for="' . $serviceId . '" class="form-check-label">' . $service['name'] . '</label>
                            </div>
                            <div class="mt-2">
                                <label><span class="' . $priceClass . '">' . $priceLabel . 
                                      '<span class="price-display">' . number_format($service['price']) . '</span></span></label>
                                <input type="hidden" name="price_' . $serviceId . '" id="price_' . $serviceId . '" value="' . $service['price'] . '">
                                <input type="hidden" name="is_per_person_' . $serviceId . '" value="' . ($isPerPerson ? '1' : '0') . '">
                                <div class="mt-1">Total: ₹<span id="total_' . $serviceId . '">0</span></div>
                                <input type="hidden" name="total_' . $serviceId . '" value="0">
                            </div>
                          </div>';
                }
                ?>
            </div>
            
            <!-- Conference Fields -->
            <div id="conference-fields" class="category-fields">
                <h4>Conference Services</h4>
                
                <?php
                // Conference services
                $conferenceServices = ['venue_conference', 'av_equipment', 'catering_conference', 'speaker_fee'];
                
                foreach ($conferenceServices as $serviceId) {
                    $service = $serviceDetails[$serviceId];
                    $isPerPerson = $service['is_per_person'];
                    $priceLabel = $isPerPerson ? 'Price per person (₹): ' : 'Fixed Price (₹): ';
                    $priceClass = $isPerPerson ? 'per-person-price' : 'fixed-price';
                    
                    echo '<div class="service-option">
                            <div class="form-check">
                                <input type="checkbox" id="' . $serviceId . '" name="options[' . $serviceId . ']" class="form-check-input service-checkbox" 
                                       data-is-per-person="' . ($isPerPerson ? 'true' : 'false') . '">
                                <label for="' . $serviceId . '" class="form-check-label">' . $service['name'] . '</label>
                            </div>
                            <div class="mt-2">
                                <label><span class="' . $priceClass . '">' . $priceLabel . 
                                      '<span class="price-display">' . number_format($service['price']) . '</span></span></label>
                                <input type="hidden" name="price_' . $serviceId . '" id="price_' . $serviceId . '" value="' . $service['price'] . '">
                                <input type="hidden" name="is_per_person_' . $serviceId . '" value="' . ($isPerPerson ? '1' : '0') . '">
                                <div class="mt-1">Total: ₹<span id="total_' . $serviceId . '">0</span></div>
                                <input type="hidden" name="total_' . $serviceId . '" value="0">
                            </div>
                          </div>';
                }
                ?>
            </div>
            
            <!-- Workshop Fields -->
            <div id="workshop-fields" class="category-fields">
                <h4>Workshop Services</h4>
                
                <?php
                // Workshop services
                $workshopServices = ['venue_workshop', 'materials', 'instructor', 'refreshments'];
                
                foreach ($workshopServices as $serviceId) {
                    $service = $serviceDetails[$serviceId];
                    $isPerPerson = $service['is_per_person'];
                    $priceLabel = $isPerPerson ? 'Price per person (₹): ' : 'Fixed Price (₹): ';
                    $priceClass = $isPerPerson ? 'per-person-price' : 'fixed-price';
                    
                    echo '<div class="service-option">
                            <div class="form-check">
                                <input type="checkbox" id="' . $serviceId . '" name="options[' . $serviceId . ']" class="form-check-input service-checkbox" 
                                       data-is-per-person="' . ($isPerPerson ? 'true' : 'false') . '">
                                <label for="' . $serviceId . '" class="form-check-label">' . $service['name'] . '</label>
                            </div>
                            <div class="mt-2">
                                <label><span class="' . $priceClass . '">' . $priceLabel . 
                                      '<span class="price-display">' . number_format($service['price']) . '</span></span></label>
                                <input type="hidden" name="price_' . $serviceId . '" id="price_' . $serviceId . '" value="' . $service['price'] . '">
                                <input type="hidden" name="is_per_person_' . $serviceId . '" value="' . ($isPerPerson ? '1' : '0') . '">
                                <div class="mt-1">Total: ₹<span id="total_' . $serviceId . '">0</span></div>
                                <input type="hidden" name="total_' . $serviceId . '" value="0">
                            </div>
                          </div>';
                }
                ?>
            </div>
        </div>
        
        <!-- Price Summary Section -->
        <div class="price-summary" id="price-summary">
            <h4>Price Summary</h4>
            <div id="summary-items">
                <!-- Summary items will be dynamically populated here -->
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <h5>Total Estimated Cost:</h5>
                <h5>₹<span id="total-price">0</span></h5>
                <input type="hidden" name="total_price" id="total_price_input" value="0">
            </div>
            <p class="text-muted small">Note: Admin may adjust these prices during review.</p>
        </div>
        
        <button type="submit" class="btn btn-primary mt-4">Submit Request</button>
    </form>
    
    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Handle event type change
        document.getElementById('event_type').addEventListener('change', function() {
            // Hide all category fields
            var categoryFields = document.querySelectorAll('.category-fields');
            categoryFields.forEach(function(field) {
                field.style.display = 'none';
            });
            
            // Uncheck all checkboxes
            document.querySelectorAll('.service-checkbox').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            
            // Show selected category fields
            var selectedCategory = this.value;
            if (selectedCategory) {
                var selectedField = document.getElementById(selectedCategory + '-fields');
                if (selectedField) {
                    selectedField.style.display = 'block';
                }
            }
            
            // Reset price display
            document.getElementById('total-price').textContent = '0';
            document.getElementById('total_price_input').value = '0';
            
            // Clear summary items
            var summaryContainer = document.getElementById('summary-items');
            summaryContainer.innerHTML = '<p class="text-muted">No services selected</p>';
            
            // Reset all service totals
            document.querySelectorAll('[id^="total_"]').forEach(function(element) {
                if (element.id !== 'total-price' && element.id !== 'total_price_input' && element.id !== 'total_attendees') {
                    element.textContent = '0';
                }
            });
            
            document.querySelectorAll('input[name^="total_"]').forEach(function(input) {
                if (input.name !== 'total_price' && input.name !== 'total_attendees') {
                    input.value = '0';
                }
            });
        });
        
        // Handle attendee count change
        document.getElementById('total_attendees').addEventListener('input', function() {
            updateAllTotals();
        });
        
        // Handle checkbox changes
        var serviceCheckboxes = document.querySelectorAll('.service-checkbox');
        serviceCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateAllTotals();
            });
        });
        
        // Update individual service total based on whether it's per-person or fixed price
        function updateServiceTotal(serviceId) {
            var checkbox = document.getElementById(serviceId);
            var priceElement = document.getElementById('price_' + serviceId);
            var totalElement = document.getElementById('total_' + serviceId);
            var totalInputElement = document.querySelector('input[name="total_' + serviceId + '"]');
            
            if (checkbox && priceElement && totalElement) {
                var isPerPerson = checkbox.getAttribute('data-is-per-person') === 'true';
                var attendees = parseInt(document.getElementById('total_attendees').value) || 0;
                var price = parseFloat(priceElement.value) || 0;
                
                var total = 0;
                if (checkbox.checked) {
                    if (isPerPerson) {
                        total = attendees * price;
                    } else {
                        total = price; // Fixed price
                    }
                }
                
                totalElement.textContent = total.toLocaleString();
                if (totalInputElement) {
                    totalInputElement.value = total;
                }
                
                return {
                    name: checkbox.nextElementSibling.textContent.trim(),
                    price: total,
                    checked: checkbox.checked
                };
            }
            
            return null;
        }
        
        // Update all service totals and overall price
        function updateAllTotals() {
            var serviceIds = [];
            document.querySelectorAll('.service-checkbox').forEach(function(checkbox) {
                serviceIds.push(checkbox.id);
            });
            
            var summaryItems = [];
            var totalPrice = 0;
            
            serviceIds.forEach(function(serviceId) {
                var serviceData = updateServiceTotal(serviceId);
                if (serviceData && serviceData.checked) {
                    summaryItems.push(serviceData);
                    totalPrice += serviceData.price;
                }
            });
            
            // Update total price
            document.getElementById('total-price').textContent = totalPrice.toLocaleString();
            document.getElementById('total_price_input').value = totalPrice;
            
            // Update summary items
            updateSummaryItems(summaryItems);
        }
        
        // Update the summary items display
        function updateSummaryItems(items) {
            var summaryContainer = document.getElementById('summary-items');
            summaryContainer.innerHTML = '';
            
            if (items.length === 0) {
                summaryContainer.innerHTML = '<p class="text-muted">No services selected</p>';
                return;
            }
            
            items.forEach(function(item) {
                var itemElement = document.createElement('div');
                itemElement.className = 'd-flex justify-content-between mb-2';
                itemElement.innerHTML = '<div>' + item.name + '</div><div>₹' + item.price.toLocaleString() + '</div>';
                summaryContainer.appendChild(itemElement);
            });
        }
        
        // Initialize the form
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state
            updateAllTotals();
            
            // Trigger category change to reset form on load
            var eventTypeSelect = document.getElementById('event_type');
            if (eventTypeSelect.value) {
                // Simulate change event to load the appropriate fields
                var event = new Event('change');
                eventTypeSelect.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>
