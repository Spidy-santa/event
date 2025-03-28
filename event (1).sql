-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2025 at 01:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `getStatusBadgeClass` (`status` VARCHAR(20)) RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    RETURN CASE 
        WHEN status = 'pending' THEN 'warning'
        WHEN status = 'approved' THEN 'success'
        WHEN status = 'rejected' THEN 'danger'
        WHEN status = 'in_progress' THEN 'info'
        ELSE 'secondary'
    END;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendee_preferences`
--

CREATE TABLE `attendee_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interest` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  `booking_date` datetime NOT NULL,
  `ticket_quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `attendee_name` varchar(255) NOT NULL,
  `attendee_email` varchar(255) NOT NULL,
  `attendee_phone` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_items`
--

CREATE TABLE `budget_items` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_templates`
--

CREATE TABLE `budget_templates` (
  `template_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `suggested_cost` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_templates`
--

INSERT INTO `budget_templates` (`template_id`, `category`, `item_name`, `suggested_cost`) VALUES
(1, 'wedding', 'Catering', 5000.00),
(2, 'wedding', 'Photography', 2000.00),
(3, 'wedding', 'Florist', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `checklists`
--

CREATE TABLE `checklists` (
  `checklist_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `task` varchar(255) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_fields`
--

CREATE TABLE `dynamic_fields` (
  `field_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dynamic_fields`
--

INSERT INTO `dynamic_fields` (`field_id`, `category`, `field_name`, `field_type`) VALUES
(1, 'wedding', 'bride_name', 'text'),
(2, 'wedding', 'groom_name', 'text'),
(3, 'conference', 'speaker_list', 'textarea');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `reg_start_date` date DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `venue` varchar(255) DEFAULT 'To be announced',
  `category` enum('wedding','conference','birthday','anniversary') NOT NULL,
  `organizer_id` int(11) NOT NULL,
  `ticket_price` decimal(10,2) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `reg_end_date` datetime DEFAULT NULL,
  `total_tickets` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `assigned_by` enum('admin','user') DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending' COMMENT 'Can be pending, approved, rejected, finalized',
  `admin_notes` text DEFAULT NULL COMMENT 'Notes from admin regarding event approval',
  `event_type` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `date`, `reg_start_date`, `location`, `venue`, `category`, `organizer_id`, `ticket_price`, `capacity`, `details`, `reg_end_date`, `total_tickets`, `image_path`, `avg_rating`, `assigned_by`, `address`, `status`, `admin_notes`, `event_type`, `created_at`) VALUES
(11, 'Live concert', NULL, '2025-04-22', '2025-03-25', 'Chennai ', 'To be announced', '', 17, 450.00, NULL, '{\"description\":\"A &quot;live concert&quot; is a public performance of music by one or more musicians, where the music is played and heard in real-time, rather than a recording\",\"time\":\"20:03\",\"reg_start_date\":\"2025-03-25\",\"reg_end_date\":\"2025-04-20\",\"total_tickets\":\"500\",\"ticket_price\":\"450\",\"total_price\":\"0\",\"additional_images\":[\"..\\/assets\\/images\\/events\\/event_additional_67e383c3e14920.33096882.webp\",\"..\\/assets\\/images\\/events\\/event_additional_67e383c3e187b7.20389775.avif\",\"..\\/assets\\/images\\/events\\/event_additional_67e383c3e1c0c1.15858570.avif\",\"..\\/assets\\/images\\/events\\/event_additional_67e383c3e1f264.73344196.avif\",\"..\\/assets\\/images\\/events\\/event_additional_67e383c3e21e42.83082676.avif\"]}', '2025-04-20 00:00:00', 500, '../assets/images/events/event_67e383c3d34582.57006015.avif', 0.00, NULL, NULL, 'pending', NULL, 'general', '2025-03-26 04:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `event_budgets`
--

CREATE TABLE `event_budgets` (
  `budget_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

CREATE TABLE `event_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_category_options`
--

CREATE TABLE `event_category_options` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('venue','catering','decoration','equipment','entertainment','other') NOT NULL,
  `per_person_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_required` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_category_options`
--

INSERT INTO `event_category_options` (`id`, `category`, `name`, `type`, `per_person_price`, `is_required`) VALUES
(1, 'wedding', 'Premium Venue', 'venue', 1000.00, 0),
(2, 'wedding', 'Basic Decoration', 'decoration', 500.00, 0),
(3, 'wedding', 'Premium Decoration', 'decoration', 1000.00, 0),
(4, 'wedding', 'Veg Food', 'catering', 800.00, 0),
(5, 'wedding', 'Non-Veg Food', 'catering', 1200.00, 0),
(6, 'birthday', 'Basic Venue', 'venue', 500.00, 0),
(7, 'birthday', 'Theme Decoration', 'decoration', 300.00, 0),
(8, 'birthday', 'Basic Food Package', 'catering', 400.00, 0),
(9, 'conference', 'Conference Hall', 'venue', 1500.00, 0),
(10, 'conference', 'AV Equipment', 'equipment', 500.00, 0),
(11, 'workshop', 'Training Room', 'venue', 800.00, 0),
(12, 'workshop', 'Workshop Materials', 'equipment', 300.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `event_components`
--

CREATE TABLE `event_components` (
  `component_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `is_optional` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_components`
--

INSERT INTO `event_components` (`component_id`, `type_id`, `name`, `description`, `base_price`, `is_optional`) VALUES
(1, 1, 'Venue Decoration', 'Full venue decoration including flowers and lighting', 1000.00, 0),
(2, 1, 'Catering', 'Full-service catering for all guests', 2000.00, 0),
(3, 1, 'Photography', 'Professional photography services', 800.00, 1),
(4, 1, 'DJ Services', 'Professional DJ and sound system', 500.00, 1),
(5, 2, 'Basic Decoration', 'Basic party decoration setup', 200.00, 0),
(6, 2, 'Food Package', 'Basic food and beverages', 500.00, 0),
(7, 2, 'Entertainment', 'Games and activities coordination', 300.00, 1),
(8, 2, 'Custom Cake', 'Customized birthday cake', 100.00, 1),
(9, 3, 'Venue Setup', 'Professional conference room setup', 1000.00, 0),
(10, 3, 'AV Equipment', 'Audio/Visual equipment rental', 500.00, 0),
(11, 3, 'Catering', 'Professional catering service', 1000.00, 1),
(12, 3, 'Registration Desk', 'Staffed registration services', 300.00, 1),
(13, 4, 'Training Room', 'Fully equipped training room', 500.00, 0),
(14, 4, 'Materials', 'Workshop materials and supplies', 200.00, 0),
(15, 4, 'Refreshments', 'Basic refreshments for attendees', 150.00, 1),
(16, 4, 'Technical Support', 'On-site technical support', 200.00, 1),
(17, 1, 'Venue Decoration', 'Full venue decoration including flowers and lighting', 1000.00, 0),
(18, 1, 'Catering', 'Full-service catering for all guests', 2000.00, 0),
(19, 1, 'Photography', 'Professional photography services', 800.00, 1),
(20, 1, 'DJ Services', 'Professional DJ and sound system', 500.00, 1),
(21, 2, 'Basic Decoration', 'Basic party decoration setup', 200.00, 0),
(22, 2, 'Food Package', 'Basic food and beverages', 500.00, 0),
(23, 2, 'Entertainment', 'Games and activities coordination', 300.00, 1),
(24, 2, 'Custom Cake', 'Customized birthday cake', 100.00, 1),
(25, 3, 'Venue Setup', 'Professional conference room setup', 1000.00, 0),
(26, 3, 'AV Equipment', 'Audio/Visual equipment rental', 500.00, 0),
(27, 3, 'Catering', 'Professional catering service', 1000.00, 1),
(28, 3, 'Registration Desk', 'Staffed registration services', 300.00, 1),
(29, 4, 'Training Room', 'Fully equipped training room', 500.00, 0),
(30, 4, 'Materials', 'Workshop materials and supplies', 200.00, 0),
(31, 4, 'Refreshments', 'Basic refreshments for attendees', 150.00, 1),
(32, 4, 'Technical Support', 'On-site technical support', 200.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_quotations`
--

CREATE TABLE `event_quotations` (
  `quotation_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `venue_price` decimal(10,2) NOT NULL,
  `catering_price` decimal(10,2) NOT NULL,
  `decoration_price` decimal(10,2) NOT NULL,
  `equipment_price` decimal(10,2) NOT NULL,
  `staff_price` decimal(10,2) NOT NULL,
  `additional_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','counter') DEFAULT 'pending',
  `created_by` enum('admin','user') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `lighting_price` decimal(10,2) DEFAULT 0.00,
  `sound_price` decimal(10,2) DEFAULT 0.00,
  `security_price` decimal(10,2) DEFAULT 0.00,
  `cleaning_price` decimal(10,2) DEFAULT 0.00,
  `transportation_price` decimal(10,2) DEFAULT 0.00,
  `counter_offer` decimal(10,2) DEFAULT NULL,
  `counter_notes` text DEFAULT NULL,
  `valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_requests`
--

CREATE TABLE `event_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `contact` varchar(20) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_details` text NOT NULL,
  `preferred_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `total_attendees` int(11) NOT NULL,
  `stage_setting` varchar(255) DEFAULT NULL,
  `food_menu` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `assigned_by` enum('admin','organizer') NOT NULL,
  `is_admin_modified` tinyint(1) NOT NULL DEFAULT 0,
  `attendee_response` text DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_budget` decimal(10,2) DEFAULT NULL,
  `guest_count` int(11) DEFAULT NULL,
  `venue_size` varchar(255) DEFAULT NULL,
  `theme_preference` text DEFAULT NULL,
  `price_quote` decimal(10,2) DEFAULT NULL,
  `user_price_counter` decimal(10,2) DEFAULT NULL,
  `admin_final_price` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_requests`
--

INSERT INTO `event_requests` (`id`, `user_id`, `name`, `address`, `contact`, `event_type`, `event_details`, `preferred_date`, `location`, `total_attendees`, `stage_setting`, `food_menu`, `status`, `assigned_by`, `is_admin_modified`, `attendee_response`, `admin_comments`, `created_at`, `event_budget`, `guest_count`, `venue_size`, `theme_preference`, `price_quote`, `user_price_counter`, `admin_final_price`, `updated_at`) VALUES
(1, 14, 'santhakumar', '12,sankar nagar 3rd street', '9345347835', 'birthday', 'wedding event with 1000 people ', '2025-02-02', 'mumbai', 0, '1000', 'Vegetarian, Non-Vegetarian', 'pending', 'admin', 0, NULL, NULL, '2025-03-22 09:31:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-24 17:43:34'),
(2, 14, 'santhakumar', '12,North Street,madurai ', '9345347835', 'wedding', 's', '2024-12-22', 'mumbai', 0, '1000', 'Vegetarian', 'approved', 'admin', 0, NULL, NULL, '2025-03-23 15:36:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-24 17:43:34'),
(3, 14, 'santhakumar', '12th, North street', '9345347835', 'workshop', 'tfgvhy', '2026-09-22', 'kerala', 100, NULL, NULL, 'pending', 'admin', 0, NULL, 'ok', '2025-03-24 17:15:33', 1100.34, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-26 05:06:34'),
(4, 19, 'santhakumar', '12,north street,madurai', '9345347835', 'wedding', 'it is a wedding function', '2025-03-26', 'maduari', 600, NULL, NULL, 'accepted', 'admin', 0, NULL, 'This is ok ,congratulations', '2025-03-26 04:49:36', 1000.00, NULL, NULL, NULL, 10000.00, 0.00, NULL, '2025-03-26 04:55:45'),
(5, 21, 'Santhakumar K', '12th street', '974636465', 'birthday', 'ewe', '2025-02-22', 'madurai', 100, NULL, NULL, 'accepted', 'admin', 0, NULL, 'aaaaa', '2025-03-27 10:12:28', 9500.00, NULL, NULL, NULL, 95000.00, 0.00, NULL, '2025-03-27 10:15:12'),
(6, 21, 'saai', '12th,north street,madurai', '974636465', 'wedding', 'it is a wedding', '2025-10-22', 'kerala', 100, NULL, NULL, 'accepted', 'admin', 0, NULL, 'ok ', '2025-03-27 10:32:11', 20000.00, NULL, NULL, NULL, 200000.00, 0.00, NULL, '2025-03-27 10:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `event_request_attachments`
--

CREATE TABLE `event_request_attachments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_request_messages`
--

CREATE TABLE `event_request_messages` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_request_types`
--

CREATE TABLE `event_request_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_request_types`
--

INSERT INTO `event_request_types` (`type_id`, `type_name`, `base_price`) VALUES
(1, 'Wedding', 5000.00),
(2, 'Birthday Party', 1000.00),
(3, 'Conference', 3000.00),
(4, 'Workshop', 1500.00),
(5, 'Wedding', 5000.00),
(6, 'Birthday Party', 1000.00),
(7, 'Conference', 3000.00),
(8, 'Workshop', 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `event_reviews`
--

CREATE TABLE `event_reviews` (
  `review_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_type_categories`
--

CREATE TABLE `event_type_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_type_categories`
--

INSERT INTO `event_type_categories` (`id`, `name`, `description`) VALUES
(1, 'wedding', NULL),
(2, 'birthday', NULL),
(3, 'conference', NULL),
(4, 'workshop', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_type_components`
--

CREATE TABLE `event_type_components` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL COMMENT 'wedding, conference, birthday, workshop, etc.',
  `component_type` varchar(50) NOT NULL COMMENT 'venue, catering, decoration, etc.',
  `is_required` tinyint(1) DEFAULT 0,
  `default_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_type_components`
--

INSERT INTO `event_type_components` (`id`, `event_type`, `component_type`, `is_required`, `default_price`) VALUES
(1, 'wedding', 'venue', 1, 2000.00),
(2, 'wedding', 'catering', 1, 1500.00),
(3, 'wedding', 'decoration', 1, 1000.00),
(4, 'wedding', 'photography', 0, 800.00),
(5, 'wedding', 'music', 0, 500.00),
(6, 'wedding', 'car_rental', 0, 300.00),
(7, 'wedding', 'flowers', 0, 400.00),
(8, 'wedding', 'cake', 0, 200.00),
(9, 'wedding', 'invitation_cards', 0, 150.00),
(10, 'conference', 'venue', 1, 1500.00),
(11, 'conference', 'av_equipment', 1, 1000.00),
(12, 'conference', 'catering', 1, 1200.00),
(13, 'conference', 'registration_desk', 0, 300.00),
(14, 'conference', 'stationery', 0, 200.00),
(15, 'conference', 'speaker_accommodation', 0, 500.00),
(16, 'conference', 'promotional_materials', 0, 400.00),
(17, 'conference', 'security', 0, 600.00),
(18, 'birthday', 'venue', 1, 500.00),
(19, 'birthday', 'catering', 1, 800.00),
(20, 'birthday', 'decoration', 1, 400.00),
(21, 'birthday', 'entertainment', 0, 300.00),
(22, 'birthday', 'cake', 0, 150.00),
(23, 'birthday', 'photography', 0, 300.00),
(24, 'birthday', 'invitation', 0, 100.00),
(25, 'birthday', 'return_gifts', 0, 200.00),
(26, 'workshop', 'venue', 1, 800.00),
(27, 'workshop', 'equipment', 1, 500.00),
(28, 'workshop', 'catering', 0, 600.00),
(29, 'workshop', 'materials', 1, 400.00),
(30, 'workshop', 'instructor_fee', 1, 1000.00),
(31, 'workshop', 'certification', 0, 200.00),
(32, 'workshop', 'marketing', 0, 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `event_type_fields`
--

CREATE TABLE `event_type_fields` (
  `field_id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','select','textarea','number','date') NOT NULL,
  `options` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_type_pricing`
--

CREATE TABLE `event_type_pricing` (
  `id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `component` varchar(100) NOT NULL,
  `price_type` enum('fixed','per_person','range') NOT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `min_price` decimal(10,2) DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizers`
--

CREATE TABLE `organizers` (
  `organizer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `payment_method` enum('Credit Card','PayPal','Bank Transfer','UPI') NOT NULL,
  `payment_status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `transaction_id` varchar(100) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_components`
--

CREATE TABLE `price_components` (
  `component_id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_type` varchar(50) NOT NULL COMMENT 'venue, catering, decoration, etc.',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) DEFAULT 1,
  `is_selected` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_quotes`
--

CREATE TABLE `price_quotes` (
  `quote_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `quote_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending' COMMENT 'Can be pending, accepted, rejected, countered',
  `quote_by` enum('admin','user') DEFAULT 'admin',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `reg_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `ticket_qty` int(11) NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_modifications`
--

CREATE TABLE `request_modifications` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modification_note` text DEFAULT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_modifications`
--

INSERT INTO `request_modifications` (`id`, `request_id`, `modified_by`, `modification_note`, `previous_status`, `created_at`) VALUES
(1, 4, 1, 'Admin sent price quotation of ₹10,000.00', 'pending', '2025-03-26 04:52:22'),
(2, 4, 1, 'Admin sent price quotation of ₹10,000.00', 'pending', '2025-03-26 04:55:24'),
(3, 5, 1, 'Admin sent price quotation of ₹95,000.00', 'pending', '2025-03-27 10:14:20'),
(4, 6, 1, 'Admin sent price quotation of ₹200,000.00', 'pending', '2025-03-27 10:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_prices`
--

CREATE TABLE `service_prices` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_per_person` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_prices`
--

INSERT INTO `service_prices` (`id`, `name`, `price`, `is_per_person`) VALUES
('av_equipment', 'Audio/Visual Equipment', 25000.00, 0),
('cake', 'Cake', 3000.00, 0),
('catering_conference', 'Catering', 750.00, 1),
('decoration_birthday', 'Decoration', 10000.00, 0),
('decoration_wedding', 'Decoration/Setting', 25000.00, 0),
('entertainment_birthday', 'Entertainment/DJ', 15000.00, 0),
('entertainment_wedding', 'Entertainment', 20000.00, 0),
('food_birthday', 'Food', 600.00, 1),
('food_nonveg', 'Food & Beverage (Non-Vegetarian)', 900.00, 1),
('food_veg', 'Food & Beverage (Vegetarian)', 700.00, 1),
('instructor', 'Instructor/Facilitator', 25000.00, 0),
('materials', 'Materials/Equipment', 400.00, 1),
('photography', 'Photography Package', 15000.00, 0),
('refreshments', 'Refreshments', 300.00, 1),
('speaker_fee', 'Speaker/Program Fee', 30000.00, 0),
('venue_birthday', 'Venue/Setting', 20000.00, 0),
('venue_conference', 'Venue Rental', 40000.00, 0),
('venue_wedding', 'Venue Rental', 50000.00, 0),
('venue_workshop', 'Venue', 20000.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_types`
--

CREATE TABLE `ticket_types` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','organizer','attendee') NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `profile_completed` tinyint(1) DEFAULT 0,
  `registered_at` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `is_approved`, `profile_completed`, `registered_at`, `is_active`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 0, '2025-03-16 16:34:54', 0),
(17, 'sakthi', 'sakthi@gmail.com', '$2y$10$nHAW0jmQUz9i4xMMrtfoLOiOft2Hj0vY.VXfV4gYR00OMQNlRnfiy', 'organizer', 1, 0, '2025-03-26 09:54:42', 1),
(18, 'santhakumar k', 'santhakumark776@gmail.com', '$2y$10$E6GarqfYPejRECEXSldUOuAJHDN7LC2wAe3v0crzM9biWhYubFQJG', 'attendee', 1, 0, '2025-03-26 10:09:27', 0),
(19, 'dasarathan', 'pdasarathan0@gmail.com', '$2y$10$amMV/sLTpqkCj4a/QXTef.hhGwSXo27fbpW1QrVHLm1nIR/HAgt5S', 'attendee', 1, 0, '2025-03-26 10:12:09', 0),
(20, 'Santhakumar K', 'santhakumar776@gamil.com', '$2y$10$H2UJObCklv9k5z9KdmV2beoGgs11095jv9Va2.naWZT63dyKcoq1i', 'organizer', 1, 0, '2025-03-27 15:32:32', 1),
(21, 'Santhakumar K', 'pk5028084@gmail.com', '$2y$10$Pk4oV.NCjsTdw6luLKFmXuQzUoi7mNnDc3LWy8aXE2oiNB8o587yi', 'attendee', 1, 0, '2025-03-27 15:40:08', 0),
(22, 'vishnu', 'compvishnu23@gmail.com', '$2y$10$DpJvb/DgzP1o0dmqRZ9BaulnSy9qaTglGHXrvDezdy3xSedG0ZT66', 'attendee', 1, 0, '2025-03-27 15:56:08', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendee_preferences`
--
ALTER TABLE `attendee_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_templates`
--
ALTER TABLE `budget_templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `checklists`
--
ALTER TABLE `checklists`
  ADD PRIMARY KEY (`checklist_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `dynamic_fields`
--
ALTER TABLE `dynamic_fields`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `organizer_id` (`organizer_id`),
  ADD KEY `idx_events_date` (`date`),
  ADD KEY `idx_events_category` (`category`);

--
-- Indexes for table `event_budgets`
--
ALTER TABLE `event_budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `event_category_options`
--
ALTER TABLE `event_category_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_components`
--
ALTER TABLE `event_components`
  ADD PRIMARY KEY (`component_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `event_quotations`
--
ALTER TABLE `event_quotations`
  ADD PRIMARY KEY (`quotation_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_requests`
--
ALTER TABLE `event_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `status_index` (`status`);

--
-- Indexes for table `event_request_attachments`
--
ALTER TABLE `event_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `event_request_messages`
--
ALTER TABLE `event_request_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `event_request_types`
--
ALTER TABLE `event_request_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `event_reviews`
--
ALTER TABLE `event_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_type_categories`
--
ALTER TABLE `event_type_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_type_components`
--
ALTER TABLE `event_type_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_type` (`event_type`,`component_type`);

--
-- Indexes for table `event_type_fields`
--
ALTER TABLE `event_type_fields`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexes for table `event_type_pricing`
--
ALTER TABLE `event_type_pricing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organizers`
--
ALTER TABLE `organizers`
  ADD PRIMARY KEY (`organizer_id`),
  ADD UNIQUE KEY `contact_email` (`contact_email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `price_components`
--
ALTER TABLE `price_components`
  ADD PRIMARY KEY (`component_id`),
  ADD KEY `quote_id` (`quote_id`);

--
-- Indexes for table `price_quotes`
--
ALTER TABLE `price_quotes`
  ADD PRIMARY KEY (`quote_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`reg_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `request_modifications`
--
ALTER TABLE `request_modifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id_index` (`request_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `service_prices`
--
ALTER TABLE `service_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendee_preferences`
--
ALTER TABLE `attendee_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_templates`
--
ALTER TABLE `budget_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `checklists`
--
ALTER TABLE `checklists`
  MODIFY `checklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dynamic_fields`
--
ALTER TABLE `dynamic_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `event_budgets`
--
ALTER TABLE `event_budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_category_options`
--
ALTER TABLE `event_category_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_components`
--
ALTER TABLE `event_components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `event_quotations`
--
ALTER TABLE `event_quotations`
  MODIFY `quotation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_requests`
--
ALTER TABLE `event_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_request_attachments`
--
ALTER TABLE `event_request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_request_messages`
--
ALTER TABLE `event_request_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_request_types`
--
ALTER TABLE `event_request_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_reviews`
--
ALTER TABLE `event_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_type_categories`
--
ALTER TABLE `event_type_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_type_components`
--
ALTER TABLE `event_type_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `event_type_fields`
--
ALTER TABLE `event_type_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_type_pricing`
--
ALTER TABLE `event_type_pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizers`
--
ALTER TABLE `organizers`
  MODIFY `organizer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_components`
--
ALTER TABLE `price_components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_quotes`
--
ALTER TABLE `price_quotes`
  MODIFY `quote_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `reg_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_modifications`
--
ALTER TABLE `request_modifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_types`
--
ALTER TABLE `ticket_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendee_preferences`
--
ALTER TABLE `attendee_preferences`
  ADD CONSTRAINT `attendee_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON UPDATE CASCADE;

--
-- Constraints for table `checklists`
--
ALTER TABLE `checklists`
  ADD CONSTRAINT `checklists_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_budgets`
--
ALTER TABLE `event_budgets`
  ADD CONSTRAINT `event_budgets_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

--
-- Constraints for table `event_components`
--
ALTER TABLE `event_components`
  ADD CONSTRAINT `event_components_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `event_request_types` (`type_id`);

--
-- Constraints for table `event_quotations`
--
ALTER TABLE `event_quotations`
  ADD CONSTRAINT `fk_quotation_request` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_request_attachments`
--
ALTER TABLE `event_request_attachments`
  ADD CONSTRAINT `event_request_attachments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_request_messages`
--
ALTER TABLE `event_request_messages`
  ADD CONSTRAINT `event_request_messages_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_request_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_reviews`
--
ALTER TABLE `event_reviews`
  ADD CONSTRAINT `event_reviews_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE;

--
-- Constraints for table `price_components`
--
ALTER TABLE `price_components`
  ADD CONSTRAINT `price_components_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `price_quotes` (`quote_id`) ON DELETE CASCADE;

--
-- Constraints for table `price_quotes`
--
ALTER TABLE `price_quotes`
  ADD CONSTRAINT `price_quotes_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
