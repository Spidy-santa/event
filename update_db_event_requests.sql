-- Add event_requests table if it doesn't exist
CREATE TABLE IF NOT EXISTS `event_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `contact` varchar(20) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_details` text NOT NULL,
  `preferred_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `total_attendees` int(11) NOT NULL,
  `selected_options` text NOT NULL COMMENT 'JSON encoded selected services with pricing',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','quotation','accepted','rejected') NOT NULL DEFAULT 'pending',
  `assigned_by` enum('admin','organizer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `event_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add admin_quotations table for negotiation workflow
CREATE TABLE IF NOT EXISTS `admin_quotations` (
  `quotation_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `service_key` varchar(50) NOT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `quoted_price` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','rejected','recote') NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`quotation_id`),
  KEY `request_id` (`request_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_quotations_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`request_id`) ON DELETE CASCADE,
  CONSTRAINT `admin_quotations_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some sample service pricing data
INSERT INTO `service_pricing` (`category`, `service_key`, `service_name`, `price_per_person`) VALUES
('wedding', 'venue_wedding', 'Venue Rental', 1500.00),
('wedding', 'decoration_wedding', 'Decoration/Setting', 800.00),
('wedding', 'food_veg', 'Food & Beverage (Vegetarian)', 700.00),
('wedding', 'food_nonveg', 'Food & Beverage (Non-Vegetarian)', 900.00),
('wedding', 'photography', 'Photography Package', 300.00),
('wedding', 'entertainment_wedding', 'Entertainment', 500.00),
('birthday', 'venue_birthday', 'Venue/Setting', 800.00),
('birthday', 'cake', 'Cake', 200.00),
('birthday', 'decoration_birthday', 'Decoration', 300.00),
('birthday', 'entertainment_birthday', 'Entertainment/DJ', 400.00),
('birthday', 'food_birthday', 'Food', 600.00),
('conference', 'venue_conference', 'Venue Rental', 1200.00),
('conference', 'av_equipment', 'Audio/Visual Equipment', 500.00),
('conference', 'catering_conference', 'Catering', 750.00),
('conference', 'speaker_fee', 'Speaker/Program Fee', 800.00),
('workshop', 'venue_workshop', 'Venue', 600.00),
('workshop', 'materials', 'Materials/Equipment', 400.00),
('workshop', 'instructor', 'Instructor/Facilitator', 900.00),
('workshop', 'refreshments', 'Refreshments', 300.00);
