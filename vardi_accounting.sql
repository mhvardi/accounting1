-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 22, 2025 at 10:12 AM
-- Server version: 10.6.21-MariaDB
-- PHP Version: 8.1.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vardi_accounting`
--

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(190) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `total_cost_amount` int(11) NOT NULL DEFAULT 0,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `note` text DEFAULT NULL,
  `sales_employee_id` int(11) DEFAULT NULL,
  `whmcs_service_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `customer_name`, `customer_id`, `category_id`, `title`, `total_amount`, `start_date`, `end_date`, `status`, `note`, `sales_employee_id`, `whmcs_service_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, '', 3, 1, 'طراحی سایت داکو', 15000000, '1404-07-03', NULL, 'active', '', NULL, NULL, NULL, '2025-11-19 13:42:53', '2025-11-19 13:42:53'),
(2, '', 4, 1, 'طراحی سایت مزرعه طلایی', 40000000, '2025-11-19', NULL, 'active', '', 12, NULL, NULL, '2025-11-19 13:48:32', '2025-11-20 14:08:42'),
(4, '', 5, 1, 'طراحی سایت غفاری', 60000000, '2025-11-20', NULL, 'active', '', 12, NULL, NULL, '2025-11-19 15:06:59', '2025-11-20 14:08:30'),
(6, '', 20, 2, 'سئو سریتا', 10000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:54:29', '2025-11-22 10:04:49'),
(7, '', 15, 2, 'سئو بیسیم', 8000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:55:06', '2025-11-22 10:04:41'),
(8, '', 19, 2, 'سئو طبرستانی', 4000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:56:36', '2025-11-22 10:04:32'),
(9, '', 11, 2, 'سئو نگار بیوتی', 7000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:57:06', '2025-11-22 09:57:06'),
(10, '', 23, 2, 'سئو پاکزاد', 5000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:57:51', '2025-11-22 10:04:24'),
(11, '', 17, 2, 'سئو لیمو اس ام اس', 7000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:58:12', '2025-11-22 10:04:16'),
(12, '', 18, 2, 'سئو شمال استوک', 5000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:58:47', '2025-11-22 10:04:08'),
(13, '', 14, 2, 'سئو تاجیک', 5000000, '2025-11-22', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 09:59:15', '2025-11-22 10:03:58'),
(14, '', 6, 1, 'طراحی سایت 7 صبح', 27000000, '2025-11-16', NULL, 'active', '', NULL, NULL, NULL, '2025-11-22 10:00:25', '2025-11-22 10:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `note`, `created_at`, `updated_at`) VALUES
(1, 'دکتر ذبیحی', '', '', '', '2025-11-19 11:52:37', '2025-11-19 11:52:46'),
(2, 'ازمایشگاه سریتا', '', '', '', '2025-11-19 12:13:13', '2025-11-19 12:13:13'),
(3, 'داکو - نادری', '', '', '', '2025-11-19 12:13:19', '2025-11-19 12:13:19'),
(4, 'حسینی-مزرعه-طلایی', '', '', '', '2025-11-19 13:47:43', '2025-11-19 13:47:54'),
(5, 'املاک کبیر غفاری', '', '', '', '2025-11-19 15:06:09', '2025-11-20 14:09:32'),
(6, 'میلاد حبیبیان 7 صبح', '', '', '', '2025-11-20 14:11:40', '2025-11-22 09:49:49'),
(7, 'دکتر مهسا اسماعیل پور', '', '', '', '2025-11-22 09:50:00', '2025-11-22 09:50:00'),
(8, 'رضا نورزاد دکتر فیت', '', '', '', '2025-11-22 09:50:09', '2025-11-22 09:50:09'),
(9, 'دکتر شقایق قاسمی', '', '', '', '2025-11-22 09:50:21', '2025-11-22 09:50:21'),
(10, 'ایمان قلی پور', '', '', '', '2025-11-22 09:50:29', '2025-11-22 09:50:29'),
(11, 'امیر صالحیان نگار بیوتی', '', '', '', '2025-11-22 09:50:41', '2025-11-22 09:50:41'),
(12, 'دکتر زهرا بابازاده', '', '', '', '2025-11-22 09:50:48', '2025-11-22 09:50:48'),
(13, 'دکتر ارتین براری', '', '', '', '2025-11-22 09:51:35', '2025-11-22 09:51:35'),
(14, 'دکتر سیما تاجیک', '', '', '', '2025-11-22 09:51:42', '2025-11-22 09:51:42'),
(15, 'سید حمید قربانی', '', '', '', '2025-11-22 09:51:48', '2025-11-22 09:51:48'),
(16, 'محبوبه ملکشاه تی تیش', '', '', '', '2025-11-22 09:51:58', '2025-11-22 09:51:58'),
(17, 'محمد رستمی کایر', '', '', '', '2025-11-22 09:52:07', '2025-11-22 09:52:07'),
(18, 'روح اله جهانی', '', '', '', '2025-11-22 09:52:16', '2025-11-22 09:52:16'),
(19, 'مریم طبرستانی', '', '', '', '2025-11-22 09:52:27', '2025-11-22 09:52:27'),
(20, 'ارمیا زاهد پاشا سریتا', '', '', '', '2025-11-22 09:52:36', '2025-11-22 09:52:36'),
(21, 'مهدی اسماعیل پور حجازی', '', '', '', '2025-11-22 09:52:54', '2025-11-22 09:52:54'),
(22, 'امیرحسین نادری', '', '', '', '2025-11-22 09:53:02', '2025-11-22 09:53:02'),
(23, 'دکتر محسن پاکزاد', '', '', '', '2025-11-22 09:57:20', '2025-11-22 09:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(190) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `base_salary` int(11) NOT NULL DEFAULT 0,
  `compensation_type` enum('fixed','commission','mixed') NOT NULL DEFAULT 'fixed',
  `commission_mode` enum('none','flat','tiered') NOT NULL DEFAULT 'none',
  `commission_scope` enum('self','company','category') NOT NULL DEFAULT 'self',
  `commission_percent` int(11) NOT NULL DEFAULT 0,
  `commission_config_json` text DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `active`, `base_salary`, `compensation_type`, `commission_mode`, `commission_scope`, `commission_percent`, `commission_config_json`, `effective_from`, `created_at`, `updated_at`) VALUES
(12, 'رزا خالق نژاد', 1, 15000000, 'mixed', 'tiered', 'self', 0, '{\"tiers\":[{\"min\":0,\"max\":50000000,\"percent\":2},{\"min\":50000001,\"max\":100000000,\"percent\":3},{\"min\":100000001,\"max\":150000000,\"percent\":4},{\"min\":150000001,\"max\":9999999999,\"percent\":5}]}', '2025-10-23', NULL, NULL),
(13, 'مهدیه رستمیان', 1, 10000000, 'mixed', 'flat', 'company', 10, '{\"tiers\":[]}', '2025-10-23', NULL, NULL),
(14, 'مبین زمان', 1, 10000000, 'mixed', 'flat', 'category', 10, '{\"tiers\":[],\"categories\":[2]}', '2025-10-23', NULL, NULL),
(15, 'غزل اسماعیلی', 1, 12000000, 'fixed', 'none', 'self', 0, '{\"tiers\":[]}', '2025-10-23', NULL, NULL),
(16, 'امیر شیری پور', 1, 10000000, 'mixed', 'flat', 'category', 10, '{\"tiers\":[],\"categories\":[1]}', '2025-10-23', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_commission_items`
--

CREATE TABLE `employee_commission_items` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `amount_base` int(11) NOT NULL DEFAULT 0,
  `commission_amount` int(11) NOT NULL DEFAULT 0,
  `payroll_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_payrolls`
--

CREATE TABLE `employee_payrolls` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `basis` enum('sales_total','cash_collected') NOT NULL DEFAULT 'sales_total',
  `sales_amount` int(11) NOT NULL DEFAULT 0,
  `commission_amount` int(11) NOT NULL DEFAULT 0,
  `base_salary` int(11) NOT NULL DEFAULT 0,
  `bonus_amount` int(11) NOT NULL DEFAULT 0,
  `advance_amount` int(11) NOT NULL DEFAULT 0,
  `other_deductions` int(11) NOT NULL DEFAULT 0,
  `total_payable` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `status` enum('draft','paid') NOT NULL DEFAULT 'draft',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(190) NOT NULL,
  `amount` bigint(20) NOT NULL DEFAULT 0,
  `expense_date` varchar(20) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category`, `amount`, `expense_date`, `customer_id`, `note`, `created_at`, `updated_at`) VALUES
(3, 'هزینه های شرکت', 650000, '2025-11-19', NULL, 'خرید شوینده', '2025-11-20 14:10:30', '2025-11-20 14:10:30');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'هزینه های شرکت', '2025-11-20 13:43:47', '2025-11-20 14:09:58'),
(2, 'دامنه', '2025-11-20 13:44:03', '2025-11-20 13:44:03'),
(3, 'هاست', '2025-11-20 13:44:13', '2025-11-20 13:44:13');

-- --------------------------------------------------------

--
-- Table structure for table `external_monthly_income`
--

CREATE TABLE `external_monthly_income` (
  `id` int(11) NOT NULL,
  `source` enum('bankshomareh','starplan') NOT NULL,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `synced_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `external_site_ledger`
--

CREATE TABLE `external_site_ledger` (
  `id` int(10) UNSIGNED NOT NULL,
  `site_name` varchar(190) NOT NULL,
  `kind` enum('income','expense') NOT NULL DEFAULT 'expense',
  `amount_rial` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `occurred_at` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_targets`
--

CREATE TABLE `monthly_targets` (
  `id` int(11) NOT NULL,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `scope` enum('company','seo','website','hosting','domain','external') NOT NULL,
  `target_amount` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `whmcs_invoice_id` int(11) DEFAULT NULL,
  `external_source` enum('none','whmcs','bankshomareh','starplan','manual') DEFAULT 'none',
  `external_ref` varchar(190) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `pay_date` varchar(20) DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` enum('paid','refunded','pending') DEFAULT 'paid',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `contract_id`, `whmcs_invoice_id`, `external_source`, `external_ref`, `amount`, `pay_date`, `paid_at`, `method`, `status`, `note`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'none', NULL, 20000000, '1404/08/28', '0000-00-00 00:00:00', 'درگاه', 'paid', '', '2025-11-19 15:04:56', '2025-11-19 15:04:56'),
(2, 4, NULL, 'none', NULL, 20000000, '1404/08/20', '0000-00-00 00:00:00', 'درگاه', 'paid', '', '2025-11-19 15:07:28', '2025-11-19 15:07:28'),
(3, 10, NULL, 'manual', NULL, 5000000, '2025-11-22', '2025-11-22 00:00:00', 'کارت به کارت', 'paid', '', '2025-11-22 10:05:11', '2025-11-22 10:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` enum('hosting','domain','seo','service','other') DEFAULT 'service',
  `billing_cycle` enum('monthly','quarterly','semiannual','annual','lifetime','free') DEFAULT 'monthly',
  `price` int(11) DEFAULT 0,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `type`, `billing_cycle`, `price`, `meta_json`, `created_at`, `updated_at`) VALUES
(1, 'هاست اشتراکی', 'hosting', 'monthly', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47'),
(2, 'دامنه IR', 'domain', 'annual', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47'),
(3, 'دامنه COM', 'domain', 'annual', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47'),
(4, 'پشتیبانی سایت', 'service', 'annual', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47'),
(5, 'سئو ماهانه', 'seo', 'monthly', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47'),
(6, 'طراحی سایت', 'service', 'lifetime', 0, '[]', '2025-11-20 19:01:47', '2025-11-20 19:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_commissionable` tinyint(1) NOT NULL DEFAULT 1,
  `has_direct_cost` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `slug`, `parent_id`, `is_primary`, `is_commissionable`, `has_direct_cost`, `created_at`, `updated_at`) VALUES
(1, 'طراحی سایت', 'website', NULL, 1, 1, 0, '2025-11-19 11:54:04', '2025-11-19 11:54:04'),
(2, 'سئو', 'seo', NULL, 1, 1, 0, '2025-11-19 11:54:11', '2025-11-19 18:05:40'),
(3, 'هاست', 'host', NULL, 0, 1, 0, '2025-11-19 11:54:16', '2025-11-19 11:54:16'),
(4, 'پیامک', 'پیامک', NULL, 0, 1, 0, '2025-11-19 11:54:42', '2025-11-19 11:54:42'),
(5, 'دامنه', 'دامنه', NULL, 0, 1, 0, '2025-11-19 11:54:57', '2025-11-19 11:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE `servers` (
  `id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `hostname` varchar(191) NOT NULL,
  `provider` varchar(190) DEFAULT NULL,
  `annual_cost` int(11) NOT NULL,
  `purchased_at` date NOT NULL,
  `renew_at` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_check_status` tinyint(1) DEFAULT 0,
  `last_check_message` varchar(255) DEFAULT NULL,
  `last_checked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_instances`
--

CREATE TABLE `service_instances` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `status` enum('active','pending','suspended','cancelled') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `access_granted` tinyint(1) DEFAULT 0,
  `billing_cycle` varchar(50) DEFAULT NULL,
  `sale_amount` int(11) NOT NULL DEFAULT 0,
  `cost_amount` int(11) NOT NULL DEFAULT 0,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_line_items`
--

CREATE TABLE `contract_line_items` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `service_instance_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `billing_cycle` varchar(50) DEFAULT NULL,
  `sale_amount` int(11) NOT NULL DEFAULT 0,
  `cost_amount` int(11) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `username` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `staff_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `phone`, `password_hash`, `role`, `staff_id`, `created_at`, `updated_at`) VALUES
(2, 'info@vardi.ir', 'mhvardi', '09119035272', 'a0a51aa3e67559f727f1d4670eb92b8e62f6763551b26954df861b951c46b3f5', 'staff', NULL, '2025-11-18 15:30:56', '2025-11-18 15:30:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_sales_employee_id` (`sales_employee_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_commission_items`
--
ALTER TABLE `employee_commission_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_employee_contract_month` (`employee_id`,`contract_id`,`year`,`month`);

--
-- Indexes for table `employee_payrolls`
--
ALTER TABLE `employee_payrolls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_month` (`employee_id`,`year`,`month`,`basis`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `external_monthly_income`
--
ALTER TABLE `external_monthly_income`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_source_month` (`source`,`year`,`month`);

--
-- Indexes for table `external_site_ledger`
--
ALTER TABLE `external_site_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `occurred_at` (`occurred_at`),
  ADD KEY `site_name` (`site_name`);

--
-- Indexes for table `monthly_targets`
--
ALTER TABLE `monthly_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_target` (`year`,`month`,`scope`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paid_at` (`paid_at`),
  ADD KEY `idx_contract_id` (`contract_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_instances`
--
ALTER TABLE `service_instances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_contract_id` (`contract_id`);

--
-- Indexes for table `contract_line_items`
--
ALTER TABLE `contract_line_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contract` (`contract_id`),
  ADD KEY `idx_service_instance` (`service_instance_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee_commission_items`
--
ALTER TABLE `employee_commission_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_payrolls`
--
ALTER TABLE `employee_payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `external_monthly_income`
--
ALTER TABLE `external_monthly_income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `external_site_ledger`
--
ALTER TABLE `external_site_ledger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_targets`
--
ALTER TABLE `monthly_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_instances`
--
ALTER TABLE `service_instances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract_line_items`
--
ALTER TABLE `contract_line_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
