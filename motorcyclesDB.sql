-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Maj 13, 2025 at 07:43 AM
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
-- Database: `motorcyclesDB`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `storeProducts`
--

CREATE TABLE `storeProducts` (
  `id` int(11) NOT NULL,
  `productname` varchar(200) NOT NULL,
  `category` varchar(200) NOT NULL,
  `price` float NOT NULL,
  `mark` varchar(100) NOT NULL,
  `rate` int(11) NOT NULL,
  `inStock` int(11) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `oldPrice` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `usedMotorcycles`
--

CREATE TABLE `usedMotorcycles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `price` float NOT NULL,
  `oldPrice` float NOT NULL,
  `productionYear` int(11) NOT NULL,
  `mcondition` varchar(50) NOT NULL,
  `mileage` float NOT NULL,
  `engineCapacity` float NOT NULL,
  `power` int(11) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `equipment` varchar(300) NOT NULL,
  `added` date NOT NULL,
  `inspection` tinyint(1) NOT NULL,
  `fees` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `pnumber` varchar(12) NOT NULL,
  `newsletter` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `storeProducts`
--
ALTER TABLE `storeProducts`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `usedMotorcycles`
--
ALTER TABLE `usedMotorcycles`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `storeProducts`
--
ALTER TABLE `storeProducts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usedMotorcycles`
--
ALTER TABLE `usedMotorcycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
