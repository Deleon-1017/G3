-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2025 at 05:41 PM
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
-- Database: `coffee`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts_payable`
--

CREATE TABLE `accounts_payable` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts_payable`
--

INSERT INTO `accounts_payable` (`id`, `invoice_number`, `po_id`, `supplier_id`, `amount`, `due_date`, `status`, `created_at`) VALUES
(1, 'INV-20250930-653', 30, 4, 2400.00, '2025-10-30', 'paid', '2025-10-06 11:26:55'),
(2, 'INV-20250930-795', 36, 4, 4500.00, '2025-10-30', 'paid', '2025-10-06 11:26:55'),
(3, 'INV-20251006-858', 31, 3, 1000.00, '2025-11-05', 'cancelled', '2025-10-06 11:26:55'),
(4, 'INV-20251006-696', 33, 4, 50000.00, '2025-11-05', 'overdue', '2025-10-06 11:26:55'),
(5, 'INV-20251006-529', 29, 3, 4000.00, '2025-11-05', 'paid', '2025-10-06 11:26:55'),
(11, 'INV-20251006-365', 42, 3, 600.00, '2025-11-05', 'cancelled', '2025-10-06 11:30:00'),
(18, 'INV-20251006-413', 43, 3, 3000.00, '2025-11-05', 'paid', '2025-10-06 11:32:18'),
(40, 'INV-20251006-579', 42, 3, 600.00, '2025-11-05', 'paid', '2025-10-06 13:27:17'),
(41, 'INV-20251006-539', 34, 4, 0.00, '2025-11-05', 'cancelled', '2025-10-06 13:34:59'),
(42, 'INV-20251006-921', 31, 3, 1000.00, '2025-11-05', 'paid', '2025-10-06 13:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `accounts_receivable`
--

CREATE TABLE `accounts_receivable` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_ledger`
--

CREATE TABLE `asset_ledger` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `transaction_type` enum('stock-in','stock-out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--

CREATE TABLE `communications` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `sender` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `communications`
--

INSERT INTO `communications` (`id`, `ticket_id`, `customer_id`, `message`, `sender`, `created_at`) VALUES
(1, 1, 4, 'kbjlbjbjbjbjbjkb.nklhk', 'agent', '2025-10-11 15:32:54'),
(2, 1, 4, 'BDSbdsbds', 'agent', '2025-10-11 15:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `code`, `notes`, `created_at`, `email`, `phone`, `address`) VALUES
(4, 'Gab De Leon', 'CUST-004', NULL, '2025-10-11 14:40:43', 'gabdeleon00@gmail.com', '09564763689', 'Sa Sunny Brooke'),
(5, 'Juan Dela Cruz', 'CUST-005', NULL, '2025-10-12 16:18:31', 'juandelacruz@gmail.com', '09123456789', 'Cavite City'),
(9, 'bdassak', 'CUST-009', NULL, '2025-10-12 17:12:33', 'ksabsabaskdka@bdfbbdow.com', '0405904389429', 'bella vista'),
(18, 'Ramon Harald', 'CUST-018', NULL, '2025-10-13 22:48:34', 'ramonharald@yahoo.com', '09123224567', 'May PBA pa pala'),
(19, 'JM Dacer', 'CUST-019', NULL, '2025-10-13 22:56:06', 'icdibdbodsod@dnasklad.com', '09876543210', 'Robert Lang'),
(20, 'Anette Rivera', 'CUST-020', NULL, '2025-10-13 23:34:46', 'anette.16@gmail.com', '09651016021', 'Tourism Management'),
(21, 'Manny Pacute', 'CUST-021', NULL, '2025-10-13 23:39:53', 'manny@gmail.com', '09097743269', 'Davao City');

-- --------------------------------------------------------

--
-- Table structure for table `customer_finance`
--

CREATE TABLE `customer_finance` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `credit_limit` decimal(12,2) DEFAULT 10000.00,
  `outstanding_balance` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_finance`
--

INSERT INTO `customer_finance` (`id`, `customer_id`, `credit_limit`, `outstanding_balance`, `created_at`, `updated_at`) VALUES
(3, 4, 10000.00, 7536.82, '2025-10-11 06:40:43', '2025-10-13 12:14:54'),
(4, 5, 20000.00, 9985.00, '2025-10-12 08:18:31', '2025-10-13 15:41:12'),
(5, 6, 10000.00, 0.00, '2025-10-12 08:29:16', '2025-10-12 08:30:03'),
(6, 7, 10000.00, 5.00, '2025-10-12 08:43:09', '2025-10-12 08:43:21'),
(8, 8, 10000.00, 200.00, '2025-10-12 08:43:46', '2025-10-13 14:38:39'),
(9, 9, 100000.00, 49986.61, '2025-10-12 09:12:33', '2025-10-13 15:21:29'),
(10, 10, 5000.00, 1500.00, '2025-10-13 01:47:05', '2025-10-13 01:47:05'),
(12, 11, 10000.00, 100.00, '2025-10-13 01:49:22', '2025-10-13 14:29:43'),
(13, 12, 10000.00, 1700.00, '2025-10-13 14:00:12', '2025-10-13 14:29:18'),
(14, 13, 10000.00, 0.00, '2025-10-13 14:00:38', '2025-10-13 14:00:38'),
(15, 14, 10000.00, 5000.00, '2025-10-13 14:20:25', '2025-10-13 14:20:40'),
(17, 15, 10000.00, 5000.00, '2025-10-13 14:22:53', '2025-10-13 14:23:07'),
(19, 16, 10000.00, 1000.00, '2025-10-13 14:28:11', '2025-10-13 14:28:42'),
(23, 17, 10000.00, 501.05, '2025-10-13 14:30:37', '2025-10-13 14:45:22'),
(34, 18, 10000.00, 1000.00, '2025-10-13 14:48:34', '2025-10-13 14:48:49'),
(36, 19, 10000.00, 1491.75, '2025-10-13 14:56:06', '2025-10-13 14:56:55'),
(38, 20, 10000.00, 83.50, '2025-10-13 15:34:46', '2025-10-13 15:38:16'),
(41, 21, 10000.00, 900.00, '2025-10-13 15:39:53', '2025-10-13 15:40:28');

-- --------------------------------------------------------

--
-- Table structure for table `customer_invoices`
--

CREATE TABLE `customer_invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'unpaid',
  `payment_status` varchar(50) DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_invoices`
--

INSERT INTO `customer_invoices` (`id`, `invoice_number`, `customer_id`, `order_id`, `items`, `subtotal`, `tax`, `total`, `status`, `payment_status`, `due_date`, `created_at`) VALUES
(1, 'SI-20251011-779', 4, 4, NULL, 0.00, 0.00, 55.00, 'pending', 'unpaid', NULL, '2025-10-11 14:47:55'),
(2, 'INV-20251011-358', 4, 4, 'null', 60.00, 15.00, 55.00, 'unpaid', 'unpaid', '2025-11-10', '2025-10-11 15:07:40'),
(3, 'INV-20251011-863', 4, 4, '[{\"id\":4,\"sales_order_id\":4,\"product_id\":39,\"description\":\"Banana (undefined)\",\"qty\":3,\"unit_price\":\"20.00\",\"line_total\":\"60.00\"}]', 60.00, 15.00, 55.00, 'unpaid', 'unpaid', '2025-11-10', '2025-10-11 15:09:26');

-- --------------------------------------------------------

--
-- Table structure for table `customer_leads`
--

CREATE TABLE `customer_leads` (
  `lead_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `lead_source` enum('Website','Referral','Email','Call','Walk-in','Other') DEFAULT 'Website',
  `interest_level` enum('Hot','Warm','Cold') DEFAULT 'Warm',
  `status` enum('New','Contacted','Qualified','Converted','Lost') DEFAULT 'New',
  `assigned_to` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `converted_customer_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_leads`
--

INSERT INTO `customer_leads` (`lead_id`, `customer_name`, `company_name`, `email`, `contact_number`, `lead_source`, `interest_level`, `status`, `assigned_to`, `remarks`, `converted_customer_id`, `created_at`, `updated_at`) VALUES
(3, 'JM Dacer', 'Robert Lang', 'icdibdbodsod@dnasklad.com', '09876543210', 'Website', 'Hot', 'Converted', 5, '', 19, '2025-10-12 18:54:10', '2025-10-13 22:56:06'),
(8, 'Ramon Harald', 'May PBA pa pala', 'ramonharald@yahoo.com', '09123224567', 'Referral', 'Hot', 'Converted', 8, '', 18, '2025-10-13 22:48:26', '2025-10-13 22:48:34'),
(9, 'Anette Rivera', 'Tourism Management', 'anette.16@gmail.com', '09651016021', 'Walk-in', 'Warm', 'Converted', 5, '', 20, '2025-10-13 23:34:39', '2025-10-13 23:34:46');

-- --------------------------------------------------------

--
-- Table structure for table `customer_segments`
--

CREATE TABLE `customer_segments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `receipt_date` datetime NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `location_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_receipts`
--

INSERT INTO `goods_receipts` (`id`, `receipt_number`, `po_id`, `receipt_date`, `received_by`, `location_id`, `notes`, `created_at`) VALUES
(13, 'GR-20250929-494', 29, '2025-09-29 23:07:20', 'System', 1, 'Auto-generated from PO: PO-20250929-988 - Supplier: Mark ', '2025-09-29 15:07:20'),
(14, 'GR-20250930-608', 30, '2025-09-30 09:27:59', 'System', 2, 'Auto-generated from PO: PO-20250930-975 - Supplier: Jhenidell', '2025-09-30 01:27:59'),
(15, 'GR-20250930-774', 31, '2025-09-30 09:36:43', 'System', 2, 'Auto-generated from PO: PO-20250930-608 - Supplier: Mark ', '2025-09-30 01:36:43'),
(16, 'GR-20250930-364', 33, '2025-09-30 09:46:16', 'System', 2, 'Auto-generated from PO: PO-20250930-046 - Supplier: Jhenidell', '2025-09-30 01:46:16'),
(17, 'GR-20250930-337', 34, '2025-09-30 10:21:55', 'System', 1, 'Auto-generated from PO: PO-20250930-977 - Supplier: Jhenidell', '2025-09-30 02:21:55'),
(18, 'GR-20250930-448', 36, '2025-09-30 10:45:35', 'System', 2, 'Auto-generated from PO: PO-20250930-464 - Supplier: JAM', '2025-09-30 02:45:35'),
(23, 'GR-20251006-013', 42, '2025-10-06 19:29:17', 'System', 1, 'Auto-generated from PO: PO-20251006-773 - Supplier: Mark ', '2025-10-06 11:29:17'),
(24, 'GR-20251006-371', 43, '2025-10-06 19:32:05', 'System', 1, 'Auto-generated from PO: PO-20251006-049 - Supplier: Mark ', '2025-10-06 11:32:05');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `expiration_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_attendance`
--

CREATE TABLE `hr_attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `break_duration` int(11) DEFAULT 0 COMMENT 'in minutes',
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','leave','holiday') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `morning_in` time DEFAULT NULL,
  `break_out` time DEFAULT NULL,
  `break_in` time DEFAULT NULL,
  `afternoon_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_attendance_logs`
--

CREATE TABLE `hr_attendance_logs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `log_time` time NOT NULL,
  `log_type` enum('in','out') NOT NULL,
  `log_category` enum('morning','break','afternoon') NOT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_departments`
--

CREATE TABLE `hr_departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_departments`
--

INSERT INTO `hr_departments` (`id`, `name`, `description`, `manager_id`, `created_at`) VALUES
(1, 'blabla', NULL, NULL, '2025-10-06 10:26:38');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `hire_date` date NOT NULL,
  `position_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `employment_type` enum('full-time','part-time','contract','probationary') NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `status` enum('active','terminated','on-leave','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_employees`
--

INSERT INTO `hr_employees` (`id`, `employee_id`, `first_name`, `last_name`, `middle_name`, `email`, `phone`, `address`, `birth_date`, `hire_date`, `position_id`, `department_id`, `employment_type`, `salary`, `status`, `profile_image`, `created_at`, `updated_at`) VALUES
(0, '1234', 'kyle', 'cabal', '', 'sheeshable@gmail.com', '1234567899', 'asdasd', '2002-12-22', '2022-02-02', 1, 1, 'part-time', 300.00, 'active', NULL, '2025-10-06 16:08:28', '2025-10-06 16:08:28');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employee_documents`
--

CREATE TABLE `hr_employee_documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_applications`
--

CREATE TABLE `hr_leave_applications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,1) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_balances`
--

CREATE TABLE `hr_leave_balances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` decimal(5,1) NOT NULL,
  `used_days` decimal(5,1) NOT NULL DEFAULT 0.0,
  `remaining_days` decimal(5,1) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_balances`
--

INSERT INTO `hr_leave_balances` (`id`, `employee_id`, `leave_type_id`, `year`, `total_days`, `used_days`) VALUES
(0, 0, 1, 2025, 15.0, 0.0),
(0, 0, 2, 2025, 15.0, 0.0),
(0, 0, 3, 2025, 5.0, 0.0),
(0, 0, 4, 2025, 105.0, 0.0),
(0, 0, 5, 2025, 7.0, 0.0),
(0, 0, 6, 2025, 30.0, 0.0);

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_types`
--

CREATE TABLE `hr_leave_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `days_allowed` int(11) NOT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_types`
--

INSERT INTO `hr_leave_types` (`id`, `name`, `description`, `days_allowed`, `paid`, `requires_approval`) VALUES
(1, 'Sick Leave', 'For when employee is sick', 15, 1, 1),
(2, 'Vacation Leave', 'For personal time off', 15, 1, 1),
(3, 'Emergency Leave', 'For unexpected emergencies', 5, 1, 1),
(4, 'Maternity Leave', 'For female employees giving birth', 105, 1, 1),
(5, 'Paternity Leave', 'For male employees after childbirth', 7, 1, 1),
(6, 'Unpaid Leave', 'Leave without pay', 30, 0, 1),
(1, 'Sick Leave', 'For when employee is sick', 15, 1, 1),
(2, 'Vacation Leave', 'For personal time off', 15, 1, 1),
(3, 'Emergency Leave', 'For unexpected emergencies', 5, 1, 1),
(4, 'Maternity Leave', 'For female employees giving birth', 105, 1, 1),
(5, 'Paternity Leave', 'For male employees after childbirth', 7, 1, 1),
(6, 'Unpaid Leave', 'Leave without pay', 30, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `hr_onboarding_checklist`
--

CREATE TABLE `hr_onboarding_checklist` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_onboarding_checklist`
--

INSERT INTO `hr_onboarding_checklist` (`id`, `employee_id`, `task_name`, `description`, `completed`, `completed_by`, `completed_at`, `due_date`) VALUES
(0, 0, 'Complete employment paperwork', NULL, 0, NULL, NULL, '2022-02-02'),
(0, 0, 'Set up email account', NULL, 0, NULL, NULL, '2022-02-03'),
(0, 0, 'Provide company ID and access cards', NULL, 0, NULL, NULL, '2022-02-04'),
(0, 0, 'Equipment setup (computer, phone, etc.)', NULL, 0, NULL, NULL, '2022-02-05'),
(0, 0, 'Orientation session', NULL, 0, NULL, NULL, '2022-02-06'),
(0, 0, 'Benefits enrollment', NULL, 0, NULL, NULL, '2022-02-07'),
(0, 0, 'IT security training', NULL, 0, NULL, NULL, '2022-02-08');

-- --------------------------------------------------------

--
-- Table structure for table `hr_payroll`
--

CREATE TABLE `hr_payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period` varchar(20) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('pending','processed','released') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payslip_path` varchar(255) DEFAULT NULL,
  `finance_transaction_id` varchar(50) DEFAULT NULL,
  `finance_sync_status` enum('pending','synced','failed') DEFAULT 'pending',
  `finance_sync_error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_payroll`
--

INSERT INTO `hr_payroll` (`id`, `employee_id`, `pay_period`, `basic_salary`, `overtime_pay`, `bonus`, `allowances`, `deductions`, `tax`, `net_pay`, `pay_date`, `status`, `created_at`, `payslip_path`, `finance_transaction_id`, `finance_sync_status`, `finance_sync_error`) VALUES
(0, 0, '2025-10', 300.00, 0.00, 0.00, 30.00, 15.00, 33.00, 282.00, '2025-10-06', 'processed', '2025-10-06 17:36:14', NULL, NULL, 'synced', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hr_positions`
--

CREATE TABLE `hr_positions` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `salary_grade` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_positions`
--

INSERT INTO `hr_positions` (`id`, `title`, `description`, `department_id`, `salary_grade`, `created_at`) VALUES
(1, 'ceo', NULL, 1, NULL, '2025-10-06 10:26:38'),
(1, 'ceo', NULL, 1, NULL, '2025-10-06 10:26:38');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `po_id`, `supplier_id`, `invoice_date`, `due_date`, `total_amount`, `status`, `created_at`) VALUES
(6, 'INV-20250930-653', 30, 4, '2025-09-30', '2025-10-30', 2400.00, 'paid', '2025-09-30 01:28:08'),
(8, 'INV-20250930-795', 36, 4, '2025-09-30', '2025-10-30', 4500.00, 'paid', '2025-09-30 02:46:06'),
(20, 'INV-20251006-858', 31, 3, '2025-10-06', '2025-11-05', 1000.00, 'cancelled', '2025-10-06 05:25:34'),
(21, 'INV-20251006-696', 33, 4, '2025-10-06', '2025-11-05', 50000.00, 'overdue', '2025-10-06 05:43:17'),
(22, 'INV-20251006-529', 29, 3, '2025-10-06', '2025-11-05', 4000.00, 'paid', '2025-10-06 05:50:16'),
(26, 'INV-20251006-365', 42, 3, '2025-10-06', '2025-11-05', 600.00, 'cancelled', '2025-10-06 11:30:00'),
(27, 'INV-20251006-413', 43, 3, '2025-10-06', '2025-11-05', 3000.00, 'paid', '2025-10-06 11:32:18'),
(28, 'INV-20251006-579', 42, 3, '2025-10-06', '2025-11-05', 600.00, 'paid', '2025-10-06 13:27:17'),
(29, 'INV-20251006-539', 34, 4, '2025-10-06', '2025-11-05', 0.00, 'cancelled', '2025-10-06 13:34:59'),
(30, 'INV-20251006-921', 31, 3, '2025-10-06', '2025-11-05', 1000.00, 'paid', '2025-10-06 13:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_reminders`
--

CREATE TABLE `invoice_reminders` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `reminder_type` varchar(50) DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_sync_logs`
--

CREATE TABLE `invoice_sync_logs` (
  `id` int(11) NOT NULL,
  `sales_order_id` int(11) NOT NULL,
  `attempt_at` datetime DEFAULT current_timestamp(),
  `http_code` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_sync_logs`
--

INSERT INTO `invoice_sync_logs` (`id`, `sales_order_id`, `attempt_at`, `http_code`, `response`, `success`) VALUES
(10, 4, '2025-10-11 14:47:55', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251011-779\"}', 1),
(11, 4, '2025-10-11 15:17:32', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251011-756\"}', 1),
(12, 5, '2025-10-11 15:40:48', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251011-854\"}', 1),
(13, 6, '2025-10-11 15:43:03', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251011-485\"}', 1),
(14, 7, '2025-10-12 12:07:07', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251012-681\"}', 1),
(15, 8, '2025-10-12 14:45:39', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251012-440\"}', 1),
(16, 9, '2025-10-12 16:20:29', 200, '{\"status\":\"success\",\"invoice_number\":\"SI-20251012-824\"}', 1),
(19, 19, '2025-10-13 20:14:54', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0),
(20, 20, '2025-10-13 22:56:55', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0),
(21, 16, '2025-10-13 23:21:29', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0),
(22, 22, '2025-10-13 23:38:16', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0),
(23, 23, '2025-10-13 23:40:28', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0),
(24, 18, '2025-10-13 23:41:12', 404, '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', 0);

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'WH-001', 'Main Storage', NULL, '2025-09-19 13:10:56'),
(2, 'WH-002', 'Front Counter', NULL, '2025-09-19 13:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `customer_id`, `amount`, `payment_date`, `note`, `created_at`) VALUES
(1, 4, 165.00, '2025-10-11', '', '2025-10-11 15:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount_allocated` decimal(12,2) NOT NULL,
  `allocated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_expenses`
--

CREATE TABLE `payroll_expenses` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `payroll_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'pcs',
  `expiration_date` date DEFAULT NULL,
  `min_qty` int(11) DEFAULT 0,
  `max_qty` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `warehouse_id` int(11) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `category`, `unit`, `expiration_date`, `min_qty`, `max_qty`, `created_at`, `updated_at`, `warehouse_id`, `total_quantity`, `unit_price`) VALUES
(6, 'SY001', 'Vanilla Syrup', 'Used for flavored lattes.', 'Syrups', 'bottle', '2026-01-01', 5, 30, '2025-09-19 13:10:56', NULL, 1, 0, NULL),
(7, 'SY002', 'Caramel Syrup', 'Used in caramel macchiatos and frappes.', 'Syrups', 'bottle', '2026-01-01', 5, 30, '2025-09-19 13:10:56', '2025-10-13 00:27:21', 1, 20, 125.00),
(9, 'CP001', '12oz Paper Cups', 'Standard serving cups for hot beverages.', 'Packaging', 'pcs', NULL, 100, 1000, '2025-09-19 13:10:56', '2025-10-13 15:41:12', 2, 344, 15.00),
(10, 'CP002', '16oz Paper Cups', 'Larger size for iced drinks.', 'Packaging', 'pcs', NULL, 100, 1000, '2025-09-19 13:10:56', '2025-10-13 15:40:28', 2, 469, 20.00),
(11, 'CP003', 'Cup Lids', 'Lids compatible with 12oz and 16oz cups.', 'Packaging', 'pcs', NULL, 200, 2000, '2025-09-19 13:10:56', '2025-10-13 15:38:16', 2, 499, 50.00),
(12, 'SW001', 'White Sugar', 'Used in all beverages.', 'Sweeteners', 'kg', '2026-03-01', 2, 20, '2025-09-19 13:10:56', '2025-10-13 00:23:58', 1, 0, 150.00),
(13, 'SW002', 'Brown Sugar', 'Preferred for iced drinks.', 'Sweeteners', 'kg', '2026-03-01', 1, 10, '2025-09-19 13:10:56', '2025-10-13 00:24:43', 2, 10, 120.00),
(15, 'OT001', 'Ice Cubes', 'Packaged ice for cold drinks.', 'Other', 'kg', '2025-10-01', 10, 100, '2025-09-19 13:10:56', '2025-10-13 00:23:19', 1, 0, 15.00),
(16, 'OT002', 'Whipped Cream', 'Used as topping for frappes.', 'Dairy', 'can', '2025-11-01', 5, 20, '2025-09-19 13:10:56', '2025-10-13 00:23:00', 2, 10, 80.00),
(33, 'PROD-20250930-588', 'beans', 'beans', '', 'kg', NULL, 900, 0, '2025-09-30 01:26:44', '2025-10-13 00:21:50', 2, 0, 100.00),
(34, 'PROD-20250930-379', 'new', 'new', '', 'pcs', NULL, 50, 100, '2025-09-30 01:35:32', '2025-09-30 01:41:40', 2, 0, NULL),
(35, 'PROD-20250930-263', 'kopi', 'kopi', '', 'g', NULL, 0, 10, '2025-09-30 01:45:14', '2025-10-13 00:21:05', 2, 0, 75.00),
(36, 'PROD-20250930-983', 'VanillaMilk', 'milk', 'beverage', 'ml', '2025-09-10', 20, 30, '2025-09-30 02:22:43', '2025-10-13 00:20:33', 1, 0, 150.00),
(38, 'PROD-20250930-852', 'chocolate syrup', 'chocolate syrup', '', 'ml', NULL, 0, 0, '2025-09-30 02:41:58', '2025-10-13 00:19:51', 2, -21, 200.00),
(39, 'OT0011', 'Banana', '', '', 'pcs', '2025-11-28', 300, 0, '2025-10-06 17:30:49', '2025-10-12 08:30:03', 1, 291, 15.00),
(40, 'OT0012', 'Milk', '', '', 'pcs', '2025-11-22', 30, 0, '2025-10-06 17:31:27', '2025-10-11 07:40:48', 2, 40, 125.00),
(41, 'OT0013', 'Yogurt', '', '', 'pcs', '2025-11-29', 50, 0, '2025-10-06 17:32:13', '2025-10-06 17:33:03', 2, 100, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_locations`
--

CREATE TABLE `product_locations` (
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_locations`
--

INSERT INTO `product_locations` (`product_id`, `location_id`, `quantity`) VALUES
(6, 1, 10),
(7, 1, 20),
(9, 2, 345),
(10, 2, 495),
(11, 2, 499),
(12, 1, 0),
(13, 2, 0),
(15, 1, 0),
(16, 2, 10),
(33, 2, 0),
(34, 2, 0),
(35, 2, 0),
(36, 1, 0),
(38, 2, 0),
(39, 1, 291),
(40, 2, 40),
(41, 2, 100);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` enum('draft','sent','confirmed','delivered','cancelled') NOT NULL DEFAULT 'draft',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `requisition_id`, `supplier_id`, `order_date`, `status`, `total_amount`, `notes`, `created_at`) VALUES
(29, 'PO-20250929-988', 15, 3, '2025-09-29 17:06:00', 'delivered', NULL, '', '2025-09-29 15:07:07'),
(30, 'PO-20250930-975', 16, 4, '2025-09-30 03:26:00', 'delivered', NULL, 'kmvdkj', '2025-09-30 01:27:22'),
(31, 'PO-20250930-608', 18, 3, '2025-09-30 03:35:00', 'delivered', NULL, '', '2025-09-30 01:36:09'),
(33, 'PO-20250930-046', 20, 4, '2025-09-30 03:45:00', 'delivered', NULL, 'kuhiu', '2025-09-30 01:45:41'),
(34, 'PO-20250930-977', 21, 4, '2025-09-30 04:21:00', 'delivered', NULL, '', '2025-09-30 02:21:45'),
(36, 'PO-20250930-464', 23, 4, '2025-09-30 04:44:00', 'delivered', NULL, 'YTUYG', '2025-09-30 02:45:05'),
(42, 'PO-20251006-773', 17, 3, '2025-10-06 13:28:00', 'delivered', NULL, 'hehe', '2025-10-06 11:28:53'),
(43, 'PO-20251006-049', NULL, 3, '2025-10-06 13:31:00', 'delivered', NULL, 'try error', '2025-10-06 11:31:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `po_id`, `product_id`, `quantity`, `unit_price`, `received_quantity`) VALUES
(20, 29, 16, 20, 200.00, 0),
(21, 30, 33, 30, 80.00, 0),
(22, 31, 34, 10, 100.00, 0),
(24, 33, 35, 500, 100.00, 0),
(27, 36, 38, 50, 90.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisitions`
--

CREATE TABLE `purchase_requisitions` (
  `id` int(11) NOT NULL,
  `requisition_number` varchar(50) NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `request_date` datetime NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requisitions`
--

INSERT INTO `purchase_requisitions` (`id`, `requisition_number`, `requested_by`, `request_date`, `status`, `description`, `approved_by`, `approval_date`, `created_at`) VALUES
(15, 'REQ-20250929-450', 'Cashier - Zam', '2025-09-29 23:06:36', '', 'Whipped Cream - OUT OF STOCK', 'Admin', '2025-09-29 23:06:42', '2025-09-29 15:06:36'),
(16, 'REQ-20250930-559', 'jhen', '2025-09-30 09:26:44', '', 'morj', 'Admin', '2025-09-30 09:26:48', '2025-09-30 01:26:44'),
(17, 'REQ-20250930-217', 'Employee - Dano', '2025-09-30 09:33:40', '', 'outof stock', 'Admin', '2025-09-30 09:34:14', '2025-09-30 01:33:40'),
(18, 'REQ-20250930-682', 'Kyle', '2025-09-30 09:35:32', '', 'out', 'Admin', '2025-09-30 09:35:38', '2025-09-30 01:35:32'),
(19, 'REQ-20250930-500', 'jhenidel', '2025-09-30 09:42:41', '', 'out of stock', 'Admin', '2025-09-30 09:42:44', '2025-09-30 01:42:41'),
(20, 'REQ-20250930-643', 'jam', '2025-09-30 09:45:14', '', 'ygig', 'Admin', '2025-09-30 09:45:18', '2025-09-30 01:45:14'),
(21, 'REQ-20250930-103', 'Cashier - Zam', '2025-09-30 10:20:31', '', 'Need Stocks', 'Admin', '2025-09-30 10:20:35', '2025-09-30 02:20:31'),
(22, 'REQ-20250930-760', 'Cashier - Zam', '2025-09-30 10:22:43', '', 'NEED', 'Admin', '2025-09-30 10:22:47', '2025-09-30 02:22:43'),
(23, 'REQ-20250930-964', 'jam', '2025-09-30 10:41:58', '', 'out of stock', 'Admin', '2025-09-30 10:42:08', '2025-09-30 02:41:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requisition_items`
--

CREATE TABLE `purchase_requisition_items` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_requisition_items`
--

INSERT INTO `purchase_requisition_items` (`id`, `requisition_id`, `product_id`, `quantity`, `estimated_unit_price`) VALUES
(14, 15, 16, 20, 200.00),
(15, 16, 33, 45, 80.00),
(16, 17, 16, 20, 100.00),
(17, 18, 34, 20, 200.00),
(19, 20, 35, 500, 100.00),
(21, 22, 36, 10, 50.00),
(22, 23, 38, 50, 90.00);

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quote_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('draft','sent','accepted','rejected') DEFAULT 'draft',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_number`, `customer_id`, `status`, `subtotal`, `discount`, `tax`, `total`, `expiry_date`, `notes`, `terms`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'Q-2025-0001', 4, 'rejected', 60.00, 30.00, 10.00, 40.00, '2026-11-14', NULL, NULL, NULL, '2025-10-11 20:29:12', '2025-10-13 23:32:39'),
(4, 'Q-2025-0002', 4, 'accepted', 60.00, 0.00, 0.00, 60.00, '2026-04-18', NULL, NULL, NULL, '2025-10-11 20:33:12', '2025-10-13 23:32:34'),
(6, 'Q-2025-0003', 5, 'sent', 1291.00, 65.00, 35.00, 1261.00, '2025-12-31', NULL, NULL, NULL, '2025-10-12 16:19:32', '2025-10-13 23:32:25');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `product_id`, `description`, `qty`, `unit_price`, `line_total`) VALUES
(11, 6, 36, 'VanillaMilk', 1, 150.00, 150.00),
(12, 6, 41, 'Yogurt', 11, 100.00, 1100.00),
(13, 4, 11, 'Cup Lids', 2, 50.00, 100.00),
(14, 3, 40, 'Milk', 6, 125.00, 750.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('pending','processed','shipped','delivered','cancelled') DEFAULT 'pending',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `finance_transaction_id` varchar(100) DEFAULT NULL,
  `finance_sync_status` enum('pending','synced','failed') DEFAULT 'pending',
  `finance_sync_error` text DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `order_number`, `customer_id`, `status`, `subtotal`, `discount`, `tax`, `total`, `created_at`, `confirmed_at`, `finance_transaction_id`, `finance_sync_status`, `finance_sync_error`, `lead_id`) VALUES
(4, 'SO-1760164846', 4, 'delivered', 60.00, 20.00, 15.00, 55.00, '2025-10-11 14:41:14', '2025-10-11 15:17:32', 'SI-20251011-756', 'synced', NULL, NULL),
(5, 'SO-1760168412', 4, 'delivered', 300.00, 0.00, 0.00, 300.00, '2025-10-11 15:40:40', '2025-10-11 15:40:48', 'SI-20251011-854', 'synced', NULL, NULL),
(6, 'SO-1760168507', 4, 'shipped', 1500.00, 50.00, 30.00, 1480.00, '2025-10-11 15:42:56', '2025-10-11 15:43:03', 'SI-20251011-485', 'synced', NULL, NULL),
(7, 'SO-1760241877', 4, 'delivered', 10.00, 50.00, 30.00, 6.50, '2025-10-12 12:06:21', '2025-10-12 12:07:07', 'SI-20251012-681', 'synced', NULL, NULL),
(8, 'SO-1760250837', 4, 'delivered', 70.00, 50.00, 50.00, 52.50, '2025-10-12 14:35:52', '2025-10-12 14:45:39', 'SI-20251012-440', 'synced', NULL, NULL),
(9, 'SO-1760257182', 5, 'delivered', 30.00, 25.00, 10.00, 24.75, '2025-10-12 16:20:18', '2025-10-12 16:20:29', 'SI-20251012-824', 'synced', NULL, NULL),
(10, 'SO-1760257552', 4, 'pending', 25.00, 10.00, 15.00, 25.87, '2025-10-12 16:26:48', NULL, NULL, 'pending', NULL, NULL),
(16, 'SO-1760321851', 9, 'shipped', 15.00, 15.00, 5.00, 13.39, '2025-10-13 10:18:35', '2025-10-13 23:21:29', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL),
(17, 'SO-1760323091', 4, 'pending', 75.00, 50.00, 15.00, 43.13, '2025-10-13 10:38:32', NULL, NULL, 'pending', NULL, NULL),
(18, 'SO-1760323183', 5, 'processed', 15.00, 0.00, 0.00, 15.00, '2025-10-13 10:39:50', '2025-10-13 23:41:12', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL),
(19, 'SO-1760357638', 4, 'delivered', 15.00, 10.00, 5.00, 14.18, '2025-10-13 20:14:45', '2025-10-13 20:14:54', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL),
(20, 'SO-1760367385', 19, 'delivered', 15.00, 50.00, 10.00, 8.25, '2025-10-13 22:56:46', '2025-10-13 22:56:55', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL),
(22, 'SO-1760369709', 20, 'processed', 50.00, 70.00, 10.00, 16.50, '2025-10-13 23:35:31', '2025-10-13 23:38:16', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL),
(23, 'SO-1760370009', 21, 'processed', 100.00, 0.00, 0.00, 100.00, '2025-10-13 23:40:24', '2025-10-13 23:40:28', NULL, 'failed', '<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 80</address>\n</body></html>\n', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

CREATE TABLE `sales_order_items` (
  `id` int(11) NOT NULL,
  `sales_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `line_total` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(5,2) DEFAULT 0.00,
  `tax` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`id`, `sales_order_id`, `product_id`, `description`, `qty`, `unit_price`, `line_total`, `discount`, `tax`) VALUES
(8, 8, 38, 'chocolate syrup (PROD-20250930-852)', 1, 70.00, 70.00, 0.00, 0.00),
(9, 9, 39, 'Banana (OT0011)', 2, 15.00, 30.00, 0.00, 0.00),
(10, 10, 13, 'Brown Sugar (SW002)', 1, 25.00, 25.00, 0.00, 0.00),
(37, 16, 9, '12oz Paper Cups (CP001)', 1, 15.00, 15.00, 0.00, 0.00),
(38, 17, 35, 'kopi (PROD-20250930-263)', 1, 75.00, 75.00, 0.00, 0.00),
(40, 19, 9, '12oz Paper Cups (CP001)', 1, 15.00, 15.00, 0.00, 0.00),
(41, 20, 9, '12oz Paper Cups (CP001)', 1, 15.00, 15.00, 0.00, 0.00),
(44, 22, 11, 'Cup Lids (CP003)', 1, 50.00, 50.00, 0.00, 0.00),
(45, 23, 10, '16oz Paper Cups (CP002)', 5, 20.00, 100.00, 0.00, 0.00),
(46, 18, 9, '12oz Paper Cups (CP001)', 1, 15.00, 15.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_from` int(11) DEFAULT NULL,
  `location_to` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `type` enum('stock-in','stock-out','transfer') NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `trans_date` datetime DEFAULT current_timestamp(),
  `user_name` varchar(100) DEFAULT 'system',
  `expiration_date` date DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` enum('po','gr','transfer','adjustment') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `product_id`, `location_from`, `location_to`, `qty`, `type`, `reference`, `note`, `trans_date`, `user_name`, `expiration_date`, `reference_id`, `reference_type`) VALUES
(20, 16, 1, NULL, 10, '', NULL, 'Stock-out', '2025-09-29 22:59:23', 'system', NULL, NULL, NULL),
(21, 16, NULL, 1, 20, 'stock-in', 'GR-20250929-494', 'Received from PO: PO-20250929-988', '2025-09-29 23:07:20', 'System', NULL, 13, 'gr'),
(22, 16, 1, 2, 1, 'transfer', NULL, '', '2025-09-29 23:18:01', 'system', NULL, NULL, NULL),
(23, 16, 1, 2, 1, 'transfer', NULL, '', '2025-09-29 23:18:22', 'system', NULL, NULL, NULL),
(24, 16, 1, 2, 20, 'transfer', NULL, '', '2025-09-29 23:22:33', 'system', NULL, NULL, NULL),
(25, 16, 1, 2, 5, 'transfer', NULL, '', '2025-09-29 23:23:33', 'system', NULL, NULL, NULL),
(26, 16, 1, 2, 10, 'transfer', NULL, '', '2025-09-29 23:24:14', 'system', NULL, NULL, NULL),
(27, 16, 1, 2, 20, 'transfer', NULL, '', '2025-09-29 23:30:31', 'system', NULL, NULL, NULL),
(28, 16, 1, NULL, 189, '', NULL, 'Stock-out', '2025-09-29 23:36:25', 'system', NULL, NULL, NULL),
(29, 16, 1, 2, 11, 'transfer', NULL, '', '2025-09-29 23:36:37', 'system', NULL, NULL, NULL),
(30, 16, 1, 2, 11, 'transfer', NULL, '', '2025-09-30 09:25:18', 'system', NULL, NULL, NULL),
(31, 33, NULL, 2, 30, 'stock-in', 'GR-20250930-608', 'Received from PO: PO-20250930-975', '2025-09-30 09:27:59', 'System', NULL, 14, 'gr'),
(32, 33, 2, 1, 20, 'transfer', NULL, '', '2025-09-30 09:30:23', 'system', NULL, NULL, NULL),
(33, 34, NULL, 2, 10, 'stock-in', 'GR-20250930-774', 'Received from PO: PO-20250930-608', '2025-09-30 09:36:43', 'System', NULL, 15, 'gr'),
(34, 34, 2, 1, 10, 'transfer', NULL, '', '2025-09-30 09:38:08', 'system', NULL, NULL, NULL),
(35, 35, NULL, 2, 500, 'stock-in', 'GR-20250930-364', 'Received from PO: PO-20250930-046', '2025-09-30 09:46:16', 'System', NULL, 16, 'gr'),
(37, 36, 1, NULL, 600, '', NULL, 'Stock-out', '2025-09-30 10:34:47', 'system', NULL, NULL, NULL),
(38, 38, NULL, 2, 50, 'stock-in', 'GR-20250930-448', 'Received from PO: PO-20250930-464', '2025-09-30 10:45:35', 'System', NULL, 18, 'gr'),
(45, 10, 2, NULL, 5, '', NULL, 'Sales order #SO-1760157977', '2025-10-11 13:12:22', 'system', NULL, NULL, NULL),
(46, 10, 2, NULL, 5, '', NULL, 'Sales order #SO-1760157977', '2025-10-11 13:14:12', 'system', NULL, NULL, NULL),
(47, 10, 2, NULL, 5, '', NULL, 'Sales order #SO-1760157977', '2025-10-11 13:18:42', 'system', NULL, NULL, NULL),
(48, 10, 2, NULL, 5, '', NULL, 'Sales order #SO-1760157977', '2025-10-11 13:20:10', 'system', NULL, NULL, NULL),
(49, 10, 2, NULL, 2, '', NULL, 'Sales order #SO-1760162560', '2025-10-11 14:03:09', 'system', NULL, NULL, NULL),
(50, 10, 2, NULL, 2, '', NULL, 'Sales order #SO-1760162560', '2025-10-11 14:03:41', 'system', NULL, NULL, NULL),
(51, 10, 2, NULL, 2, '', NULL, 'Sales order #SO-1760162560', '2025-10-11 14:07:50', 'system', NULL, NULL, NULL),
(52, 39, 1, NULL, 3, '', NULL, 'Sales order #SO-1760164846', '2025-10-11 14:47:55', 'system', NULL, NULL, NULL),
(53, 39, 1, NULL, 3, '', NULL, 'Sales order #SO-1760164846', '2025-10-11 15:17:32', 'system', NULL, NULL, NULL),
(54, 40, 2, NULL, 10, '', NULL, 'Sales order #SO-1760168412', '2025-10-11 15:40:48', 'system', NULL, NULL, NULL),
(55, 38, 2, NULL, 20, '', NULL, 'Sales order #SO-1760168507', '2025-10-11 15:43:03', 'system', NULL, NULL, NULL),
(56, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760241877', '2025-10-12 12:07:07', 'system', NULL, NULL, NULL),
(57, 38, 2, NULL, 1, '', NULL, 'Sales order #SO-1760250837', '2025-10-12 14:45:39', 'system', NULL, NULL, NULL),
(58, 39, 1, NULL, 2, '', NULL, 'Sales order #SO-1760257182', '2025-10-12 16:20:29', 'system', NULL, NULL, NULL),
(59, 39, 1, NULL, 1, '', NULL, 'Sales order #SO-1760257775', '2025-10-12 16:30:03', 'system', NULL, NULL, NULL),
(60, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760315961', '2025-10-13 08:48:26', 'system', NULL, NULL, NULL),
(61, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760357638', '2025-10-13 20:14:54', 'system', NULL, NULL, NULL),
(62, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760367385', '2025-10-13 22:56:55', 'system', NULL, NULL, NULL),
(63, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760321851', '2025-10-13 23:21:29', 'system', NULL, NULL, NULL),
(64, 11, 2, NULL, 1, '', NULL, 'Sales order #SO-1760369709', '2025-10-13 23:38:16', 'system', NULL, NULL, NULL),
(65, 10, 2, NULL, 5, '', NULL, 'Sales order #SO-1760370009', '2025-10-13 23:40:28', 'system', NULL, NULL, NULL),
(66, 9, 2, NULL, 1, '', NULL, 'Sales order #SO-1760323183', '2025-10-13 23:41:12', 'system', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_info` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `performance_rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `code`, `name`, `contact_info`, `address`, `performance_rating`, `created_at`) VALUES
(3, 'SC-001', 'Mark ', '09218090652', 'none', 4.00, '2025-09-21 17:25:35'),
(4, 'SC-002', 'JAM', 'jhrngmail\r\n2541684', 'uhuuhn', 4.00, '2025-09-29 15:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `assigned_to` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `ticket_number`, `customer_id`, `subject`, `description`, `status`, `priority`, `assigned_to`, `created_at`, `closed_at`) VALUES
(1, 'T-1760167959', 4, 'hiohfdhfeofew', 'fhaofhefheofoafhadufhfkbfefb', 'open', 'medium', NULL, '2025-10-11 15:32:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','sales','support') DEFAULT 'sales',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `created_at`) VALUES
(5, 'Nathaniel Himarangan', 'natnatmo@gmail.com', 'sales', '2025-10-12 16:11:31'),
(6, 'Kyle Cabal', 'kylecabal@gmail.com', 'support', '2025-10-12 16:11:31'),
(7, 'Mark Anthony Dano', 'danyomark@gmail.com', 'admin', '2025-10-12 16:11:31'),
(8, 'Renoel Jersean Ocampo', 'bossrenoelmapagmahal@gmail.com', 'sales', '2025-10-12 16:11:31'),
(9, 'Kyle Raven Mabignay', 'mabignay@example.com', 'support', '2025-10-12 16:11:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `asset_ledger`
--
ALTER TABLE `asset_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `communications`
--
ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_finance`
--
ALTER TABLE `customer_finance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customer_invoices`
--
ALTER TABLE `customer_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `customer_leads`
--
ALTER TABLE `customer_leads`
  ADD PRIMARY KEY (`lead_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_customer_leads_converted_customer` (`converted_customer_id`);

--
-- Indexes for table `customer_segments`
--
ALTER TABLE `customer_segments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `po_item_id` (`po_item_id`);

--
-- Indexes for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_date` (`employee_id`,`attendance_date`),
  ADD KEY `attendance_date` (`attendance_date`);

--
-- Indexes for table `hr_attendance_logs`
--
ALTER TABLE `hr_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `log_date` (`log_date`);

--
-- Indexes for table `hr_departments`
--
ALTER TABLE `hr_departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `invoice_sync_logs`
--
ALTER TABLE `invoice_sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_order_id` (`sales_order_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `payroll_expenses`
--
ALTER TABLE `payroll_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_products_warehouse` (`warehouse_id`);

--
-- Indexes for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD PRIMARY KEY (`product_id`,`location_id`),
  ADD UNIQUE KEY `uq_product` (`product_id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`location_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`);

--
-- Indexes for table `purchase_requisition_items`
--
ALTER TABLE `purchase_requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_order_id` (`sales_order_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_from` (`location_from`),
  ADD KEY `location_to` (`location_to`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `reference_id` (`reference_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `asset_ledger`
--
ALTER TABLE `asset_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communications`
--
ALTER TABLE `communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customer_finance`
--
ALTER TABLE `customer_finance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `customer_invoices`
--
ALTER TABLE `customer_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_leads`
--
ALTER TABLE `customer_leads`
  MODIFY `lead_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer_segments`
--
ALTER TABLE `customer_segments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_sync_logs`
--
ALTER TABLE `invoice_sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_expenses`
--
ALTER TABLE `payroll_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `purchase_requisitions`
--
ALTER TABLE `purchase_requisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `purchase_requisition_items`
--
ALTER TABLE `purchase_requisition_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD CONSTRAINT `accounts_payable_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `accounts_payable_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `asset_ledger`
--
ALTER TABLE `asset_ledger`
  ADD CONSTRAINT `asset_ledger_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `stock_transactions` (`id`),
  ADD CONSTRAINT `asset_ledger_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `communications`
--
ALTER TABLE `communications`
  ADD CONSTRAINT `communications_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `communications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_leads`
--
ALTER TABLE `customer_leads`
  ADD CONSTRAINT `customer_leads_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lead_customer` FOREIGN KEY (`converted_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `fk_gr_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipts_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`);

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `fk_gr_items_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gr_items_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoice_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  ADD CONSTRAINT `invoice_reminders_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `customer_invoices` (`id`);

--
-- Constraints for table `invoice_sync_logs`
--
ALTER TABLE `invoice_sync_logs`
  ADD CONSTRAINT `invoice_sync_logs_ibfk_1` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `accounts_receivable` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD CONSTRAINT `product_locations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_locations_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_po_items_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requisition_items`
--
ALTER TABLE `purchase_requisition_items`
  ADD CONSTRAINT `fk_requisition_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_requisition_items_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `sales_order_items_ibfk_1` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`location_from`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`location_to`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transactions_ibfk_4` FOREIGN KEY (`reference_id`) REFERENCES `goods_receipts` (`id`);

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
