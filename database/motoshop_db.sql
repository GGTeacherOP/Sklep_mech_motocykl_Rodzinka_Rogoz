-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Cze 04, 2025 at 09:59 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motoshop_db`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Alpinestars', 'alpinestars', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Dainese', 'dainese', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'HJC', 'hjc', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(4, 'Shoei', 'shoei', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(5, 'Motul', 'motul', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(6, 'Dunlop', 'dunlop', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(7, 'Honda', 'honda', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(8, 'Yamaha', 'yamaha', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(9, 'Suzuki', 'suzuki', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(10, 'Kawasaki', 'kawasaki', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(11, 'BMW', 'bmw', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(12, 'Ducati', 'ducati', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(13, 'AGV', 'agv', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(14, 'Castrol', 'castrol', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(15, 'DID', 'did', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(16, 'Galfer', 'galfer', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(17, 'Shido', 'shido', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(18, 'Michelin', 'michelin', NULL, NULL, '2025-05-21 21:08:54', '2025-05-21 21:08:54'),
(19, 'Yusa', 'yusa', NULL, NULL, '2025-06-04 19:19:53', '2025-06-04 19:19:53'),
(20, 'Rk', 'rk', NULL, NULL, '2025-06-04 19:20:59', '2025-06-04 19:20:59'),
(21, 'Sidi Mag', 'sidimag', NULL, NULL, '2025-06-04 19:21:55', '2025-06-04 19:21:55'),
(22, 'Brembo', 'brembo', NULL, NULL, '2025-06-04 19:23:24', '2025-06-04 19:23:24'),
(23, 'Pirelli', 'pirelli', NULL, NULL, '2025-06-04 19:24:14', '2025-06-04 19:24:14');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, '2025-05-19 18:47:49', '2025-05-19 18:47:49'),
(2, 5, NULL, '2025-05-21 20:08:25', '2025-05-21 20:08:25'),
(3, 6, NULL, '2025-05-26 11:39:27', '2025-05-26 11:39:27'),
(4, 3, NULL, '2025-05-26 21:45:56', '2025-05-26 21:45:56');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(3, 1, 1, 1, '2025-05-19 19:40:14', '2025-05-19 19:40:14'),
(45, 3, 20, 3, '2025-05-26 11:39:27', '2025-05-26 11:39:30'),
(46, 3, 7, 4, '2025-05-26 11:41:46', '2025-05-26 11:41:47');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `created_at`, `updated_at`) VALUES
(1, 'Kaski', 'kaski', 'Kaski motocyklowe różnych typów i marek', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Odzież', 'odziez', 'Odzież motocyklowa letnia i zimowa', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'Części', 'czesci', 'Części zamienne do motocykli', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(4, 'Oleje i chemia', 'oleje', 'Oleje, smary i inne produkty chemiczne', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(5, 'Akumulatory', 'akumulatory', 'Akumulatory do różnych typów motocykli', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(6, 'Akcesoria', 'akcesoria', 'Akcesoria motocyklowe i wyposażenie dodatkowe', NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `mechanics`
--

CREATE TABLE `mechanics` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`id`, `name`, `specialization`, `experience`, `rating`, `image_path`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Jan Kowalski', 'Honda, Yamaha', 15, 4.5, 'uploads/mechanics/mechanic1.jpg', 'Doświadczony mechanik z 15-letnim stażem. Specjalizuje się w motocyklach japońskich.', 'active', '2025-05-19 12:49:34', '2025-05-21 19:48:30'),
(2, 'Piotr Nowak', 'BMW, Ducati', 10, 5.0, 'uploads/mechanics/mechanic2.jpg', 'Ekspert w motocyklach europejskich. Certyfikowany mechanik BMW i Ducati.', 'active', '2025-05-19 12:49:34', '2025-05-21 19:48:30'),
(3, 'Anna Wiśniewska', 'Suzuki, Kawasaki', 5, 4.0, 'uploads/mechanics/mechanic3.jpg', 'Młoda, ambitna mechanik z pasją do motocykli sportowych.', 'active', '2025-05-19 12:49:34', '2025-05-21 19:48:30');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `mechanic_reviews`
--

CREATE TABLE `mechanic_reviews` (
  `id` int(11) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `motorcycle_images`
--

CREATE TABLE `motorcycle_images` (
  `id` int(11) NOT NULL,
  `motorcycle_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motorcycle_images`
--

INSERT INTO `motorcycle_images` (`id`, `motorcycle_id`, `image_path`, `is_main`, `created_at`) VALUES
(1, 2, 'uploads/motorcycles/mt07.jpg', 1, '2025-05-26 18:52:50'),
(2, 1, 'uploads/motorcycles/cbr600rr.jpg', 1, '2025-05-26 18:53:47'),
(3, 3, 'uploads/motorcycles/bmwr1250gs.jpg', 1, '2025-05-26 18:53:47'),
(4, 4, 'uploads/motorcycles/TriumphTripleRS.jpg', 1, '2025-05-26 19:10:54'),
(5, 5, 'uploads/motorcycles/SuperDuke1290.jpg', 1, '2025-05-26 19:10:54'),
(6, 6, 'uploads/motorcycles/GSXR-1000.jpg', 1, '2025-05-26 19:11:30'),
(7, 7, 'uploads/motorcycles/RSV4.jpg', 1, '2025-05-26 19:11:30'),
(8, 9, 'uploads/motorcycles/scout-bobber-black.jpg', 1, '2025-05-26 19:11:52'),
(9, 8, 'uploads/motorcycles/guzzi.jpg', 1, '2025-05-26 19:12:39');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `motorcycle_viewings`
--

CREATE TABLE `motorcycle_viewings` (
  `id` int(11) NOT NULL,
  `motorcycle_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `payment_method` enum('cash','transfer','card','online') NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `order_date`, `total_amount`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_method`, `subtotal`, `shipping_method`, `shipping_cost`, `total`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 5, 'MS-20250521-AEDE6', 'pending', '2025-05-21 22:21:26', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-21 20:21:26', '2025-05-24 12:23:43'),
(2, 5, 'MS-20250521-A7285', 'pending', '2025-05-21 22:23:31', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-21 20:23:31', '2025-05-24 12:23:43'),
(3, 5, 'MS-20250521-14321', 'processing', '2025-05-21 22:27:00', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-21 20:27:00', '2025-05-24 12:23:43'),
(4, 5, 'MS-20250521-CF06D', 'pending', '2025-05-21 23:00:38', 854.97, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'card', 839.97, 'courier', 15.00, 854.97, 'pending', '2025-05-21 21:00:38', '2025-05-24 12:23:43'),
(5, 5, 'MS-20250521-53845', 'processing', '2025-05-21 23:26:00', 1044.96, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 1029.96, 'courier', 15.00, 1044.96, 'pending', '2025-05-21 21:26:00', '2025-05-24 12:23:43'),
(6, 5, 'MS-20250521-33511', 'processing', '2025-05-21 23:29:49', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-21 21:29:49', '2025-05-24 12:23:43'),
(7, 5, 'MS-20250521-394E3', 'processing', '2025-05-21 23:34:00', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-21 21:34:00', '2025-05-24 12:23:43'),
(8, 5, 'MS-20250521-48386', 'processing', '2025-05-21 23:36:54', 664.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 649.99, 'courier', 15.00, 664.99, 'pending', '2025-05-21 21:36:54', '2025-05-24 12:23:43'),
(9, 5, 'MS-20250521-36A38', 'processing', '2025-05-21 23:38:52', 264.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 249.99, 'courier', 15.00, 264.99, 'pending', '2025-05-21 21:38:52', '2025-05-24 12:23:43'),
(10, 5, 'MS-20250521-42496', 'processing', '2025-05-21 23:45:46', 264.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 249.99, 'courier', 15.00, 264.99, 'pending', '2025-05-21 21:45:46', '2025-05-24 12:23:43'),
(11, 5, 'MS-20250521-1DE6E', 'processing', '2025-05-21 23:49:21', 294.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 279.99, 'courier', 15.00, 294.99, 'pending', '2025-05-21 21:49:21', '2025-05-24 12:23:43'),
(12, 5, 'MS-20250521-43EDA', 'processing', '2025-05-21 23:50:57', 134.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 119.99, 'courier', 15.00, 134.99, 'pending', '2025-05-21 21:50:57', '2025-05-24 12:23:43'),
(13, 5, 'MS-20250521-1579B', 'cancelled', '2025-05-21 23:52:32', 264.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 249.99, 'courier', 15.00, 264.99, 'pending', '2025-05-21 21:52:32', '2025-05-24 12:23:43'),
(14, 5, 'MS-20250523-03B17', 'pending', '2025-05-23 16:25:59', 254.98, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 239.98, 'courier', 15.00, 254.98, 'pending', '2025-05-23 14:25:59', '2025-05-24 12:23:43'),
(15, 5, 'MS-20250523-F2108', 'shipped', '2025-05-23 16:32:47', 654.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 639.99, 'courier', 15.00, 654.99, 'pending', '2025-05-23 14:32:47', '2025-05-26 12:25:27'),
(16, 5, 'MS-20250524-2D2AC', 'shipped', '2025-05-24 14:21:25', 144.99, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', 'Szczeka 4', 'Rytwiany', '28-236', 'online', 129.99, 'courier', 15.00, 0.00, 'pending', '2025-05-24 12:21:25', '2025-05-26 12:17:32'),
(17, 3, 'MS-20250526-65B8E', 'pending', '2025-05-26 23:46:04', 254.98, 'Maciej', 'Rodzinka', 'maciek.rodzinka@gmail.com', '782383709', 'Dębowa 6', 'Mielec', '39-300', 'online', 239.98, 'courier', 15.00, 0.00, 'pending', '2025-05-26 21:46:04', '2025-05-26 21:46:04'),
(18, 3, 'MS-20250526-D70B6', 'shipped', '2025-05-26 23:47:53', 254.98, 'Maciej', 'Rodzinka', 'maciek.rodzinka@gmail.com', '782383709', 'Dębowa 6', 'Mielec', '39-300', 'online', 239.98, 'courier', 15.00, 0.00, 'pending', '2025-05-26 21:47:53', '2025-05-26 21:50:18');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 1, '', 1, 639.99, '2025-05-21 20:21:26'),
(3, 3, 1, '', 1, 639.99, '2025-05-21 20:27:00'),
(4, 4, NULL, '', 3, 279.99, '2025-05-21 21:00:38'),
(5, 5, 7, '', 1, 129.99, '2025-05-21 21:26:00'),
(6, 5, 3, '', 1, 399.99, '2025-05-21 21:26:00'),
(7, 5, 20, '', 2, 249.99, '2025-05-21 21:26:00'),
(8, 6, 1, '', 1, 639.99, '2025-05-21 21:29:49'),
(9, 7, 1, '', 1, 639.99, '2025-05-21 21:34:00'),
(10, 8, 16, '', 1, 649.99, '2025-05-21 21:36:54'),
(11, 9, 20, '', 1, 249.99, '2025-05-21 21:38:52'),
(12, 10, 20, '', 1, 249.99, '2025-05-21 21:45:46'),
(13, 11, 10, '', 1, 279.99, '2025-05-21 21:49:21'),
(14, 12, 17, '', 1, 119.99, '2025-05-21 21:50:57'),
(15, 13, 20, '', 1, 249.99, '2025-05-21 21:52:32'),
(16, 14, 17, '', 2, 119.99, '2025-05-23 14:25:59'),
(17, 15, 1, '', 1, 639.99, '2025-05-23 14:32:47'),
(18, 16, 7, '', 1, 129.99, '2025-05-24 12:21:25'),
(19, 18, 17, 'Olej silnikowy Castrol Power 1 Racing 4T', 2, 119.99, '2025-05-26 21:47:53');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(50) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('published','draft','out_of_stock') DEFAULT 'published',
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `stock`, `sku`, `featured`, `status`, `category_id`, `brand_id`, `created_at`, `updated_at`) VALUES
(1, 'Kask motocyklowy HJC C10', 'kask-motocyklowy-hjc-c10', 'Wizjer z powłoką UV chroni przed promieniowaniem UV\r\nZapięcie mikrometryczne łatwe w obsłudze nawet w rękawicy\r\nPrzystosowany do montażu Pinlocka dla zapobiegania parowaniu\r\nWizjer z powłoką anti-scratch zapewnia ochronę przed zarysowaniami\r\nSkorupa Advanced Polycarbonate Composite oferuje wysoką odporność na uszkodzenia.', NULL, 659.99, 639.99, 115, NULL, 0, 'published', 1, 3, '2025-05-19 17:59:20', '2025-05-26 11:34:12'),
(2, 'Kask motocyklowy Shoei NXR2', 'kask-motocyklowy-shoei-nxr2', 'Kask motocyklowy Shoei NXR2 to najnowsza wersja popularnego modelu. Wykonany z kompozytu AIM+, posiada system wentylacji CWR-1, a także jest kompatybilny z systemem Sena SRL.', 'Kask motocyklowy Shoei NXR2 - najwyższa jakość i bezpieczeństwo', 1299.99, 1199.99, 50, 'SHO-NXR2-001', 1, 'published', 1, 4, '2025-05-21 21:02:49', '2025-05-26 11:34:35'),
(3, 'Rękawice motocyklowe Alpinestars GP Pro', 'r-kawice-motocyklowe-alpinestars-gp-pro', 'Rękawice motocyklowe Alpinestars GP Pro to profesjonalne rękawice sportowe. Wykonane ze skóry bydlęcej z dodatkowymi wzmocnieniami, posiadają system wentylacji i ochronę kostek.', 'Rękawice motocyklowe Alpinestars GP Pro - profesjonalna ochrona', 399.99, NULL, 74, 'ALP-GPP-001', 0, 'published', 2, 1, '2025-05-21 21:02:49', '2025-05-26 11:34:48'),
(4, 'Kurtka motocyklowa Dainese Super Speed', 'kurtka-motocyklowa-dainese-super-speed', 'Kurtka motocyklowa Dainese Super Speed to lekka i przewiewna kurtka sportowa. Wykonana z materiału D-Skin, posiada system wentylacji i wymienne ochraniacze.', 'Kurtka motocyklowa Dainese Super Speed - lekkość i ochrona', 1499.99, 1399.99, 40, 'DAI-SS-001', 1, 'published', 2, 2, '2025-05-21 21:02:49', '2025-05-26 11:35:00'),
(5, 'Spodnie motocyklowe Alpinestars Bionic Pro', 'spodnie-motocyklowe-alpinestars-bionic-pro', 'Spodnie motocyklowe Alpinestars Bionic Pro to spodnie z wbudowanymi ochraniaczami. Wykonane z materiału stretch, posiadają system wentylacji i wymienne ochraniacze.', 'Spodnie motocyklowe Alpinestars Bionic Pro - ochrona i komfort', 799.99, NULL, 60, 'ALP-BP-001', 0, 'published', 2, 1, '2025-05-21 21:02:49', '2025-05-26 11:35:13'),
(6, 'Buty motocyklowe Sidi Mag-1', 'buty-motocyklowe-sidi-mag-1', 'Buty motocyklowe Sidi Mag-1 to profesjonalne buty sportowe. Wykonane ze skóry z dodatkowymi wzmocnieniami, posiadają podeszwę antypoślizgową i system zapięcia.', 'Buty motocyklowe Sidi Mag-1 - profesjonalna ochrona stóp', 599.99, 549.99, 45, 'SID-MAG1-001', 0, 'published', 2, 21, '2025-05-21 21:02:49', '2025-06-04 19:22:33'),
(7, 'Olej silnikowy Motul 300V 4T', 'olej-silnikowy-motul-300v-4t', 'Olej silnikowy Motul 300V 4T to olej syntetyczny najwyższej jakości. Wysoka wydajność i ochrona silnika, odpowiedni dla nowoczesnych motocykli sportowych.', 'Olej silnikowy Motul 300V 4T - najwyższa jakość', 129.99, NULL, 148, 'MOT-300V-001', 0, 'published', 4, 5, '2025-05-21 21:02:49', '2025-05-24 12:21:25'),
(8, 'Łańcuch napędowy RK 520GXW', 'a-cuch-nap-dowy-rk-520gxw', 'Łańcuch napędowy RK 520GXW to łańcuch z powłoką X-Ring. Wysoka wytrzymałość i trwałość, odpowiedni dla motocykli sportowych i turystycznych.', 'Łańcuch napędowy RK 520GXW - trwałość i wydajność', 449.99, 399.99, 55, 'RK-520GXW-001', 0, 'published', 3, 20, '2025-05-21 21:02:49', '2025-06-04 19:21:12'),
(9, 'Hamulce tarczowe Brembo Serie Oro', 'hamulce-tarczowe-brembo-serie-oro', 'Tarcze hamulcowe Brembo Serie Oro to profesjonalne tarcze sportowe. Wysoka wydajność hamowania, odpowiednie dla motocykli sportowych.', 'Hamulce tarczowe Brembo Serie Oro - profesjonalne hamowanie', 799.99, NULL, 30, 'BRE-SO-001', 1, 'published', 3, 22, '2025-05-21 21:02:49', '2025-06-04 19:23:39'),
(10, 'Akumulator motocyklowy Yuasa YTX14-BS', 'akumulator-motocyklowy-yuasa-ytx14-bs', 'Akumulator motocyklowy Yuasa YTX14-BS to akumulator 12V 12Ah. Wysoka wydajność i trwałość, odpowiedni dla większych motocykli.', 'Akumulator motocyklowy Yuasa YTX14-BS - niezawodność', 299.99, 279.99, 64, 'YUA-YTX14-001', 0, 'published', 5, 19, '2025-05-21 21:02:49', '2025-06-04 19:20:23'),
(11, 'Opony motocyklowe Pirelli Diablo Rosso IV', 'opony-motocyklowe-pirelli-diablo-rosso-iv', 'Opony motocyklowe Pirelli Diablo Rosso IV to opony sportowe z doskonałą przyczepnością. Długa żywotność, odpowiednie dla motocykli sportowych.', 'Opony motocyklowe Pirelli Diablo Rosso IV - sportowa przyczepność', 999.99, 949.99, 35, 'PIR-DR4-001', 1, 'published', 3, 23, '2025-05-21 21:02:49', '2025-06-04 19:24:24'),
(12, 'Kask motocyklowy AGV K6', 'kask-motocyklowy-agv-k6', 'Kask motocyklowy AGV K6 to nowoczesny kask sportowy z kompozytu włókna węglowego. Posiada system wentylacji, wyjmowaną wkładkę i jest kompatybilny z systemem komunikacji.', 'Kask motocyklowy AGV K6 - nowoczesność i bezpieczeństwo', 1499.99, 0.00, 25, 'AGV-K6-001', 1, 'published', 1, 1, '2025-05-21 21:07:05', '2025-05-21 21:21:38'),
(13, 'Rękawice motocyklowe Dainese 4 Stroke Evo', 'r-kawice-motocyklowe-dainese-4-stroke-evo', 'Rękawice motocyklowe Dainese 4 Stroke Evo to uniwersalne rękawice sportowe. Wykonane ze skóry bydlęcej z dodatkowymi wzmocnieniami, posiadają system wentylacji i ochronę kostek.', 'Rękawice motocyklowe Dainese 4 Stroke Evo - uniwersalna ochrona', 349.99, NULL, 40, 'DAI-4SE-001', 0, 'published', 2, 2, '2025-05-21 21:07:05', '2025-05-21 21:21:38'),
(14, 'Kurtka motocyklowa Alpinestars GP Plus R', 'kurtka-motocyklowa-alpinestars-gp-plus-r', 'Kurtka motocyklowa Alpinestars GP Plus R to profesjonalna kurtka sportowa. Wykonana z materiału 600D, posiada system wentylacji, wymienne ochraniacze i jest kompatybilna z systemem Airbag.', 'Kurtka motocyklowa Alpinestars GP Plus R - profesjonalna ochrona', 1999.99, 1899.99, 15, 'ALP-GPPR-001', 1, 'published', 2, 1, '2025-05-21 21:07:05', '2025-05-26 11:37:12'),
(15, 'Spodnie motocyklowe Dainese Super Speed Textile', 'spodnie-motocyklowe-dainese-super-speed-textile', 'Spodnie motocyklowe Dainese Super Speed Textile to spodnie z materiału tekstylnego. Posiadają wbudowane ochraniacze, system wentylacji i są kompatybilne z kurtkami Dainese.', 'Spodnie motocyklowe Dainese Super Speed Textile - lekkość i ochrona', 899.99, NULL, 30, 'DAI-SST-001', 0, 'published', 2, 2, '2025-05-21 21:07:05', '2025-05-21 21:21:38'),
(16, 'Buty motocyklowe Alpinestars SMX-6 V2', 'buty-motocyklowe-alpinestars-smx-6-v2', 'Buty motocyklowe Alpinestars SMX-6 V2 to uniwersalne buty sportowe. Wykonane ze skóry z dodatkowymi wzmocnieniami, posiadają podeszwę antypoślizgową i system zapięcia.', 'Buty motocyklowe Alpinestars SMX-6 V2 - uniwersalna ochrona', 699.99, 649.99, 34, 'ALP-SMX6-001', 0, 'published', 2, 1, '2025-05-21 21:07:05', '2025-05-26 11:37:56'),
(17, 'Olej silnikowy Castrol Power 1 Racing 4T', 'olej-silnikowy-castrol-power-1-racing-4t', 'Olej silnikowy Castrol Power 1 Racing 4T to olej syntetyczny najwyższej jakości. Wysoka wydajność i ochrona silnika, odpowiedni dla nowoczesnych motocykli sportowych.', 'Olej silnikowy Castrol Power 1 Racing 4T - maksymalna wydajność', 119.99, NULL, 95, 'CAS-P1R-001', 0, 'published', 4, 14, '2025-05-21 21:07:05', '2025-05-26 21:47:53'),
(18, 'Łańcuch napędowy DID 520VX3', 'a-cuch-nap-dowy-did-520vx3', 'Łańcuch napędowy DID 520VX3 to łańcuch z powłoką X-Ring. Wysoka wytrzymałość i trwałość, odpowiedni dla motocykli sportowych i turystycznych.', 'Łańcuch napędowy DID 520VX3 - trwałość i wydajność', 499.99, 449.99, 45, 'DID-520VX3-001', 0, 'published', 3, 15, '2025-05-21 21:07:05', '2025-05-26 11:38:23'),
(19, 'Hamulce tarczowe Galfer Wave', 'hamulce-tarczowe-galfer-wave-1', 'Tarcze hamulcowe Galfer Wave to profesjonalne tarcze sportowe. Wysoka wydajność hamowania, odpowiednie dla motocykli sportowych.', 'Hamulce tarczowe Galfer Wave - profesjonalne hamowanie', 899.99, NULL, 20, 'GAL-WAVE-001', 1, 'published', 3, 16, '2025-05-21 21:08:54', '2025-05-26 11:38:34'),
(20, 'Akumulator motocyklowy Shido YTX14-BS', 'akumulator-motocyklowy-shido-ytx14-bs-1', 'Akumulator motocyklowy Shido YTX14-BS to akumulator 12V 12Ah. Wysoka wydajność i trwałość, odpowiedni dla większych motocykli.', 'Akumulator motocyklowy Shido YTX14-BS - niezawodność', 279.99, 249.99, 46, 'SHI-YTX14-001', 0, 'published', 5, 17, '2025-05-21 21:08:54', '2025-05-26 11:38:46'),
(21, 'Opony motocyklowe Michelin Power 5', 'opony-motocyklowe-michelin-power-5-1', 'Opony motocyklowe Michelin Power 5 to opony sportowe z doskonałą przyczepnością. Długa żywotność, odpowiednie dla motocykli sportowych.', 'Opony motocyklowe Michelin Power 5 - sportowa przyczepność', 1099.99, 999.99, 25, 'MIC-P5-001', 1, 'published', 3, 18, '2025-05-21 21:08:54', '2025-06-04 19:24:57');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_main`, `created_at`) VALUES
(1, 1, 'uploads\\products\\682b76cd26623_pol_pl_Kask-enduro-Leatt-Moto-3-5-V22-czerwony-153233_2.jpg', 1, '2025-05-19 18:22:05'),
(2, 2, 'uploads\\products\\nxr2-black_7Y2SW-9999x600-resize.jpg', 1, '2025-05-26 12:32:59'),
(3, 3, 'uploads\\products\\alpinestars-gp-pro-r4-3556724-10-1.jpg', 1, '2025-05-26 12:35:07'),
(4, 4, 'uploads\\products\\500_500_productGfx_00c0bf00c907ae0157b5ed7135c7987a.jpg', 1, '2025-05-26 12:35:07'),
(5, 5, 'uploads\\products\\6507523-13-alpinestars-bionic-pro-1.jpg', 1, '2025-05-26 12:36:24'),
(6, 6, 'uploads\\products\\pol_pm_Buty-motocyklowe-SIDI-MAG-1-67763_2.jpg', 1, '2025-05-26 12:36:24'),
(7, 7, 'uploads\\products\\pol_pm_Olej-silnikowy-MOTUL-300V-FACTORY-LINE-ROAD-10W-40-4T-1L-3345_1.jpg', 1, '2025-05-26 12:37:55'),
(8, 8, 'uploads/products/eb6d05f44b249d9c2376d13642a4.jpg', 1, '2025-05-26 12:37:55'),
(9, 9, 'uploads/products/LmpwZw.jpg', 1, '2025-05-26 12:39:00'),
(10, 10, 'uploads/products/yuasa-ytx14-bs-12v-12ah.jpg', 1, '2025-05-26 12:39:00'),
(11, 11, 'uploads\\products\\pirelli_moto_diablo_rosso_4_base_992x992.png', 1, '2025-05-26 12:39:51'),
(12, 12, 'uploads/products/Kask-AGV-K6-S-Black-Matt.jpg', 1, '2025-05-26 12:39:51'),
(13, 13, 'uploads\\products\\Rekawice-DAINESE-4-Stroke-Evo-1-800x800.jpg', 1, '2025-05-26 12:41:00'),
(14, 14, 'uploads\\products\\3100520-1100-alpinestars_gp-plus-r-v3-1.jpg', 1, '2025-05-26 12:41:00'),
(15, 15, 'uploads\\products\\skorzane-spodnie-dainese-super-speed-pants-perf.jpg', 1, '2025-05-26 12:42:23'),
(16, 16, 'uploads\\products\\pol_pm_Buty-sportowe-ALPINESTARS-SMX-6-V2-BLACK-BLACK-czarny-13406_1.jpg', 1, '2025-05-26 12:42:23'),
(17, 17, 'uploads/products/YmFjYzhiM2QuanBn.jpg', 1, '2025-05-26 12:43:18'),
(18, 18, 'uploads/products/8.jpg', 1, '2025-05-26 12:43:18'),
(19, 19, 'uploads\\products\\galfer-wave-mtb-2-216987-f-sk7-w780-h554_1.jpg', 1, '2025-05-26 12:46:41'),
(20, 20, 'uploads\\products\\akumulator-litowo-jonowy-shido-ltx14-bs-lion--s--li-ion-12v-4ah_2.jpg', 1, '2025-05-26 12:46:41'),
(21, 21, 'uploads\\products\\1000x1000_michelin_power_5_6074238712e90.jpg', 1, '2025-05-26 12:47:08');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `created_at`, `updated_at`) VALUES
(1, 'Przegląd okresowy', 'Podstawowy przegląd motocykla zgodnie z zaleceniami producenta', 250.00, 120, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Naprawa', 'Naprawa usterek mechanicznych', 300.00, 180, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'Diagnostyka', 'Pełna diagnostyka komputerowa', 150.00, 60, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(4, 'Konserwacja', 'Konserwacja i przygotowanie motocykla do sezonu lub zimowania', 200.00, 90, '2025-05-19 12:49:34', '2025-05-19 12:49:34');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `service_bookings`
--

CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `mechanic_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_bookings`
--

INSERT INTO `service_bookings` (`id`, `user_id`, `mechanic_id`, `service_id`, `booking_date`, `booking_time`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, '2025-05-29', '11:00:00', 'Moja uwaga', 'confirmed', '2025-05-19 16:02:53', '2025-05-26 19:45:27'),
(2, 5, 1, 1, '2025-05-24', '10:00:00', '', 'pending', '2025-05-23 14:19:34', '2025-05-23 14:19:34');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `shop_settings`
--

CREATE TABLE `shop_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `input_type` enum('text','textarea','number','email','select','checkbox','color','file','date') DEFAULT 'text',
  `options` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_settings`
--

INSERT INTO `shop_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `is_public`, `description`, `input_type`, `options`, `created_at`, `updated_at`) VALUES
(1, 'shop_name', 'MotoShop', 'general', 1, 'Nazwa sklepu', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(2, 'shop_email', 'kontakt@motoshop.pl', 'general', 1, 'Główny adres email sklepu', 'email', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(3, 'shop_phone', '+48 123 456 789', 'general', 1, 'Główny numer telefonu sklepu', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(4, 'shop_address', 'ul. Motocyklowa 15, 00-001 Warszawa', 'general', 1, 'Adres sklepu', 'textarea', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(5, 'shop_working_hours', 'Pon-Pt: 9:00-17:00, Sob: 10:00-14:00', 'general', 1, 'Godziny otwarcia sklepu', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(6, 'shop_description', 'Sklep motocyklowy z najlepszymi częściami i akcesoriami', 'general', 1, 'Krótki opis sklepu', 'textarea', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(7, 'shop_logo', 'assets/images/logo.png', 'general', 1, 'Logo sklepu', 'file', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(8, 'shop_favicon', 'assets/images/favicon.ico', 'general', 1, 'Favicon sklepu', 'file', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(9, 'maintenance_mode', '0', 'general', 0, 'Tryb konserwacji (1 = włączony, 0 = wyłączony)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(10, 'maintenance_message', 'Sklep jest w trakcie konserwacji. Przepraszamy za utrudnienia.', 'general', 1, 'Komunikat wyświetlany w trybie konserwacji', 'textarea', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(11, 'smtp_host', 'smtp.example.com', 'email', 0, 'Host SMTP', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(12, 'smtp_port', '587', 'email', 0, 'Port SMTP', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(13, 'smtp_username', 'user@example.com', 'email', 0, 'Nazwa użytkownika SMTP', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(14, 'smtp_password', '', 'email', 0, 'Hasło SMTP', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(15, 'smtp_encryption', 'tls', 'email', 0, 'Szyfrowanie SMTP (tls, ssl)', 'select', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(16, 'email_sender_name', 'MotoShop', 'email', 0, 'Nazwa nadawcy wiadomości email', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(17, 'order_notification_email', 'zamowienia@motoshop.pl', 'email', 0, 'Email do powiadomień o zamówieniach', 'email', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(18, 'contact_form_email', 'kontakt@motoshop.pl', 'email', 0, 'Email do formularza kontaktowego', 'email', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(19, 'meta_title', 'MotoShop - Sklep motocyklowy', 'seo', 1, 'Domyślny tytuł strony', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(20, 'meta_description', 'Najlepszy sklep motocyklowy online. Oferujemy części, akcesoria i odzież motocyklową.', 'seo', 1, 'Domyślny opis meta', 'textarea', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(21, 'meta_keywords', 'motocykle, części motocyklowe, akcesoria motocyklowe, kaski', 'seo', 1, 'Domyślne słowa kluczowe', 'textarea', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(22, 'google_analytics_id', '', 'seo', 0, 'ID Google Analytics', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(23, 'facebook_pixel_id', '', 'seo', 0, 'ID Facebook Pixel', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(24, 'currency', 'PLN', 'sales', 1, 'Waluta sklepu', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(25, 'currency_symbol', 'zł', 'sales', 1, 'Symbol waluty', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(26, 'vat_rate', '23', 'sales', 1, 'Podstawowa stawka VAT (%)', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(27, 'min_order_value', '0', 'sales', 1, 'Minimalna wartość zamówienia', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(28, 'free_shipping_threshold', '200', 'sales', 1, 'Wartość zamówienia dla darmowej dostawy', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(29, 'allow_guest_checkout', '1', 'sales', 0, 'Zezwalaj na zakupy bez rejestracji (1 = tak, 0 = nie)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(30, 'default_order_status', 'pending', 'sales', 0, 'Domyślny status nowego zamówienia', 'select', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(31, 'stock_management', '1', 'sales', 0, 'Zarządzanie stanem magazynowym (1 = włączone, 0 = wyłączone)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(32, 'low_stock_threshold', '5', 'sales', 0, 'Próg niskiego stanu magazynowego', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(33, 'enable_reviews', '1', 'reviews', 0, 'Włącz recenzje produktów (1 = włączone, 0 = wyłączone)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(34, 'reviews_require_approval', '1', 'reviews', 0, 'Wymagaj zatwierdzenia recenzji (1 = tak, 0 = nie)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(35, 'allow_guest_reviews', '1', 'reviews', 0, 'Zezwalaj na recenzje od niezalogowanych użytkowników (1 = tak, 0 = nie)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(36, 'show_customer_name', '1', 'reviews', 0, 'Pokaż imię klienta w recenzjach (1 = tak, 0 = nie)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(37, 'min_review_length', '10', 'reviews', 0, 'Minimalna długość treści recenzji', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(38, 'reviews_per_page', '10', 'reviews', 0, 'Liczba recenzji na stronę', 'number', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(39, 'facebook_url', '', 'social', 1, 'Link do profilu na Facebooku', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(40, 'instagram_url', '', 'social', 1, 'Link do profilu na Instagramie', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(41, 'youtube_url', '', 'social', 1, 'Link do kanału YouTube', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(42, 'twitter_url', '', 'social', 1, 'Link do profilu na Twitterze', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(43, 'linkedin_url', '', 'social', 1, 'Link do profilu na LinkedIn', 'text', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53'),
(44, 'enable_sharing', '1', 'social', 0, 'Włącz przyciski udostępniania (1 = włączone, 0 = wyłączone)', 'checkbox', NULL, '2025-05-26 19:33:53', '2025-05-26 19:33:53');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `used_motorcycles`
--

CREATE TABLE `used_motorcycles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `mileage` int(11) NOT NULL,
  `engine_capacity` int(11) NOT NULL,
  `power` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `registration_number` varchar(20) DEFAULT NULL,
  `vin` varchar(50) DEFAULT NULL,
  `condition` enum('excellent','very_good','good','average','poor') NOT NULL,
  `status` enum('available','reserved','sold','hidden') DEFAULT 'available',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `used_motorcycles`
--

INSERT INTO `used_motorcycles` (`id`, `title`, `brand`, `model`, `year`, `mileage`, `engine_capacity`, `power`, `color`, `price`, `description`, `features`, `registration_number`, `vin`, `condition`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(1, 'Honda CBR 600RR - Stan idealny', 'Honda', 'CBR 600RR', 2018, 12000, 599, 120, 'Czerwony', 35000.00, 'Motocykl w idealnym stanie technicznym i wizualnym...', 'ABS,Quickshifter,Tryby jazdy,Podgrzewane manetki', NULL, NULL, 'excellent', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Yamaha MT-07 - Niski przebieg', 'Yamaha', 'MT-07', 2020, 5000, 689, 74, 'Czarny', 29000.00, 'Motocykl z niskim przebiegiem, regularnie serwisowany...', 'ABS,Alarm,Akcesoryjny wydech', NULL, NULL, 'very_good', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'BMW R1250GS Adventure - Pełne wyposażenie', 'BMW', 'R1250GS Adventure', 2021, 15000, 1254, 136, 'Biały', 75000.00, 'Turystyczny motocykl z pełnym wyposażeniem. Idealny na długie trasy.', 'ABS,Tempomat,Nawigacja,Podgrzewane manetki,Kufry,Quickshifter', NULL, NULL, 'excellent', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(4, 'Triumph Street Triple RS', 'Triumph', 'Street Triple RS', 2021, 9500, 765, 123, 'Biały', 45900.00, 'Sportowy naked bike w stanie idealnym. Pełna elektronika.', 'Quick Shifter, Tryby jazdy, Elektroniczna regulacja zawieszenia', 'WWL 78901', 'SMTT20VX5MB123456', 'excellent', 'available', 1, '2025-05-26 19:03:08', '2025-05-26 19:03:08'),
(5, 'KTM 1290 Super Duke R', 'KTM', '1290 Super Duke R', 2020, 12000, 1301, 180, 'Pomarańczowy', 69900.00, 'Najmocniejszy naked bike w ofercie. Pełna elektronika.', 'Launch Control, Quick Shifter, Tryby jazdy, Cornering ABS', 'WWL 89012', 'VBKEXM402LM123456', 'very_good', 'available', 1, '2025-05-26 19:03:08', '2025-05-26 19:03:08'),
(6, 'Suzuki GSX-R1000', 'Suzuki', 'GSX-R1000', 2019, 16500, 999, 202, 'Niebieski', 54900.00, 'Legendarny superbike w świetnym stanie. Pełna elektronika.', 'Launch Control, Quick Shifter, Tryby jazdy, S-DMS', 'WWL 90123', 'JS1GR7KA8K2101234', 'very_good', 'available', 0, '2025-05-26 19:03:08', '2025-05-26 19:03:08'),
(7, 'Aprilia RSV4 1100', 'Aprilia', 'RSV4 1100', 2021, 7500, 1100, 217, 'Czarny', 79900.00, 'Włoski superbike w stanie idealnym. Pełna elektronika.', 'Launch Control, Quick Shifter, Tryby jazdy, APRC', 'WWL 01234', 'ZAPRSV4A0MB123456', 'excellent', 'reserved', 1, '2025-05-26 19:03:08', '2025-05-26 19:03:08'),
(8, 'Moto Guzzi V85 TT', 'Moto Guzzi', 'V85 TT', 2020, 14500, 853, 80, 'Żółty', 42900.00, 'Elegancki motocykl turystyczny w dobrym stanie. Idealny na wypady za miasto.', 'Tryby jazdy, ABS, Traction Control, Centralny bagażnik', 'WWL 12340', 'ZGUKDH0T0LM123456', 'good', 'available', 0, '2025-05-26 19:03:08', '2025-05-26 19:03:08'),
(9, 'Indian Scout Bobber', 'Indian', 'Scout Bobber', 2019, 18500, 1133, 100, 'Czarny', 49900.00, 'Stylowy cruiser w świetnym stanie. Charakterystyczny wygląd i dźwięk.', 'ABS, Tryby jazdy, LED światła, Własny styl', 'WWL 23450', '54UDB30C0KB123456', 'very_good', 'available', 0, '2025-05-26 19:03:08', '2025-05-26 19:03:08');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `apple_id` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','mechanic','owner') NOT NULL DEFAULT 'user',
  `newsletter` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `google_id`, `apple_id`, `role`, `newsletter`, `created_at`, `updated_at`, `address`, `city`, `postal_code`, `reset_token`, `reset_token_expires`) VALUES
(1, 'Admin', 'Admin', 'admin@motoshop.pl', '123456789', '$2y$10$92IOy1KN4xkbGpVaKnS0qO7rZ48uBEfBu2oEQ0671Z95YBhqPMcJW', NULL, NULL, 'admin', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34', NULL, NULL, NULL, NULL, NULL),
(2, 'Jan', 'Nowak', 'jan.nowak@example.com', '987654321', '$2y$10$92IOy1KN4xkbGpVaKnS0qO7rZ48uBEfBu2oEQ0671Z95YBhqPMcJW', NULL, NULL, 'user', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34', NULL, NULL, NULL, NULL, NULL),
(3, 'Maciej', 'Rodzinka', 'maciek.rodzinka@gmail.com', '782383709', '$2y$10$Cy.bhEd504GhMbyaZnQa3.9PINnDN/dc0ns/V7UYR2TEbfEkEhPBO', NULL, NULL, 'admin', 0, '2025-05-19 12:56:29', '2025-05-26 19:16:34', 'Dębowa 6', 'Mielec', '39-300', NULL, NULL),
(4, 'Admin', 'Ad', 'admin@gmail.com', '675353112', '$2y$10$LWy/kEvL6uivpC7yakx3LuyeDiHbgM1BXtKBMBie1J8Rldm4sUZ0S', NULL, NULL, 'mechanic', 0, '2025-05-19 16:15:36', '2025-05-26 13:00:56', NULL, NULL, NULL, NULL, NULL),
(5, 'Bartłomiej', 'Rogóz', 'bartibarti58@gmail.com', '123456789', '$2y$10$3S8IrMvpfh95UiTkp1ztsOL0kBGRCVxCeDZriBsD.hlAHv6v3ZWFm', NULL, NULL, 'admin', 0, '2025-05-21 20:08:16', '2025-06-04 19:54:42', NULL, NULL, NULL, NULL, NULL),
(6, 'Barti', 'R', 'br.rogoz@gmail.com', '123456789', '$2y$10$35FvCfjS6TZc57YJcyPzA.07IGBsYL0OO2gXGgUevnWB7ziI.zapi', NULL, NULL, 'user', 0, '2025-05-26 11:08:45', '2025-05-26 11:08:45', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `id` int(11) NOT NULL,
  `wishlist_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeksy dla tabeli `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indeksy dla tabeli `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `mechanic_reviews`
--
ALTER TABLE `mechanic_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mechanic_id` (`mechanic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `motorcycle_images`
--
ALTER TABLE `motorcycle_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motorcycle_id` (`motorcycle_id`);

--
-- Indeksy dla tabeli `motorcycle_viewings`
--
ALTER TABLE `motorcycle_viewings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motorcycle_id` (`motorcycle_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indeksy dla tabeli `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeksy dla tabeli `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mechanic_id` (`mechanic_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indeksy dla tabeli `shop_settings`
--
ALTER TABLE `shop_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_group` (`setting_group`);

--
-- Indeksy dla tabeli `used_motorcycles`
--
ALTER TABLE `used_motorcycles`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_apple_id` (`apple_id`);

--
-- Indeksy dla tabeli `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wishlist_id` (`wishlist_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mechanics`
--
ALTER TABLE `mechanics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mechanic_reviews`
--
ALTER TABLE `mechanic_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motorcycle_images`
--
ALTER TABLE `motorcycle_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `motorcycle_viewings`
--
ALTER TABLE `motorcycle_viewings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shop_settings`
--
ALTER TABLE `shop_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `used_motorcycles`
--
ALTER TABLE `used_motorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mechanic_reviews`
--
ALTER TABLE `mechanic_reviews`
  ADD CONSTRAINT `mechanic_reviews_ibfk_1` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mechanic_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `motorcycle_images`
--
ALTER TABLE `motorcycle_images`
  ADD CONSTRAINT `motorcycle_images_ibfk_1` FOREIGN KEY (`motorcycle_id`) REFERENCES `used_motorcycles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motorcycle_viewings`
--
ALTER TABLE `motorcycle_viewings`
  ADD CONSTRAINT `motorcycle_viewings_ibfk_1` FOREIGN KEY (`motorcycle_id`) REFERENCES `used_motorcycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `motorcycle_viewings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `service_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_bookings_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `wishlist_items_ibfk_1` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
