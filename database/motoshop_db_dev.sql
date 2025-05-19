-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Maj 19, 2025 at 10:24 PM
-- Wersja serwera: 10.4.28-MariaDB
-- Wersja PHP: 8.2.4

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
(12, 'Ducati', 'ducati', NULL, NULL, '2025-05-19 12:49:34', '2025-05-19 12:49:34');

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
(1, 4, NULL, '2025-05-19 18:47:49', '2025-05-19 18:47:49');

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
(3, 1, 1, 1, '2025-05-19 19:40:14', '2025-05-19 19:40:14');

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
(1, 'Jan Kowalski', 'Honda, Yamaha', 15, 4.5, NULL, 'Doświadczony mechanik z 15-letnim stażem. Specjalizuje się w motocyklach japońskich.', 'active', '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Piotr Nowak', 'BMW, Ducati', 10, 5.0, NULL, 'Ekspert w motocyklach europejskich. Certyfikowany mechanik BMW i Ducati.', 'active', '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'Anna Wiśniewska', 'Suzuki, Kawasaki', 5, 4.0, NULL, 'Młoda, ambitna mechanik z pasją do motocykli sportowych.', 'active', '2025-05-19 12:49:34', '2025-05-19 12:49:34');

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
  `total_amount` decimal(10,2) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `payment_method` enum('cash','transfer','card','online') NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Kask motocyklowy HJC C10', 'kask-motocyklowy-hjc-c10', 'Wizjer z powłoką UV chroni przed promieniowaniem UV\r\nZapięcie mikrometryczne łatwe w obsłudze nawet w rękawicy\r\nPrzystosowany do montażu Pinlocka dla zapobiegania parowaniu\r\nWizjer z powłoką anti-scratch zapewnia ochronę przed zarysowaniami\r\nSkorupa Advanced Polycarbonate Composite oferuje wysoką odporność na uszkodzenia.', NULL, 659.99, 639.99, 120, NULL, 0, 'published', 1, 2, '2025-05-19 17:59:20', '2025-05-19 18:22:05');

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
(1, 1, 'uploads/products/682b76cd26623_pol_pl_Kask-enduro-Leatt-Moto-3-5-V22-czerwony-153233_2.jpg', 1, '2025-05-19 18:22:05');

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
(1, 3, 1, 1, '2025-05-29', '11:00:00', 'Moja uwaga', 'pending', '2025-05-19 16:02:53', '2025-05-19 16:02:53');

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
(1, 'Honda CBR 600RR - Stan idealny', 'Honda', 'CBR 600RR', 2018, 12000, 599, 120, 'Czerwony', 35000.00, 'Motocykl w idealnym stanie technicznym i wizualnym. Pierwszy właściciel, serwisowany w ASO. Kupiony w polskim salonie.', 'ABS,Quickshifter,Tryby jazdy,Podgrzewane manetki', NULL, NULL, 'excellent', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(2, 'Yamaha MT-07 - Niski przebieg', 'Yamaha', 'MT-07', 2020, 5000, 689, 74, 'Czarny', 29000.00, 'Motocykl z niskim przebiegiem, regularnie serwisowany. Dołączam komplet dokumentów i drugi komplet opon.', 'ABS,Alarm,Akcesoryjny wydech', NULL, NULL, 'very_good', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34'),
(3, 'BMW R1250GS Adventure - Pełne wyposażenie', 'BMW', 'R1250GS Adventure', 2021, 15000, 1254, 136, 'Biały', 75000.00, 'Turystyczny motocykl z pełnym wyposażeniem. Idealny na dalekie podróże. Sprzedaję z powodu zakupu nowszego modelu.', 'ABS,Tempomat,Nawigacja,Podgrzewane manetki,Kufry,Quickshifter,Elektroniczne zawieszenie', NULL, NULL, 'excellent', 'available', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34');

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
  `role` enum('user','admin') DEFAULT 'user',
  `newsletter` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `google_id`, `apple_id`, `role`, `newsletter`, `created_at`, `updated_at`, `address`, `city`, `postal_code`) VALUES
(1, 'Admin', 'Admin', 'admin@motoshop.pl', '123456789', '$2y$10$92IOy1KN4xkbGpVaKnS0qO7rZ48uBEfBu2oEQ0671Z95YBhqPMcJW', NULL, NULL, 'admin', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34', NULL, NULL, NULL),
(2, 'Jan', 'Nowak', 'jan.nowak@example.com', '987654321', '$2y$10$92IOy1KN4xkbGpVaKnS0qO7rZ48uBEfBu2oEQ0671Z95YBhqPMcJW', NULL, NULL, 'user', 0, '2025-05-19 12:49:34', '2025-05-19 12:49:34', NULL, NULL, NULL),
(3, 'Maciej', 'Rodzinka', 'maciek.rodzinka@gmail.com', '782383709', '$2y$10$Cy.bhEd504GhMbyaZnQa3.9PINnDN/dc0ns/V7UYR2TEbfEkEhPBO', NULL, NULL, 'user', 0, '2025-05-19 12:56:29', '2025-05-19 16:01:27', 'Dębowa 6', 'Mielec', '39-300'),
(4, 'Admin', 'Ad', 'admin@gmail.com', '675353112', '$2y$10$LWy/kEvL6uivpC7yakx3LuyeDiHbgM1BXtKBMBie1J8Rldm4sUZ0S', NULL, NULL, 'admin', 0, '2025-05-19 16:15:36', '2025-05-19 16:16:05', NULL, NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motorcycle_viewings`
--
ALTER TABLE `motorcycle_viewings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `used_motorcycles`
--
ALTER TABLE `used_motorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
