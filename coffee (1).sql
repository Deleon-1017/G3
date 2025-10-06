-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 07:37 PM
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
(39, 'OT0011', 'Banana', '', '', 'pcs', '2025-11-28', 300, 0, '2025-10-06 17:30:49', '2025-10-06 17:33:16', 1, 300, 15.00),
(40, 'OT0012', 'Milk', '', '', 'pcs', '2025-11-22', 30, 0, '2025-10-06 17:31:27', '2025-10-06 17:33:09', 2, 50, 125.00),
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
(39, 1, 300),
(40, 2, 50),
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
-- Indexes for table `asset_ledger`
--
ALTER TABLE `asset_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

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
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `asset_ledger`
--
ALTER TABLE `asset_ledger`
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
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`location_from`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`location_to`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transactions_ibfk_4` FOREIGN KEY (`reference_id`) REFERENCES `goods_receipts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
