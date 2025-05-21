-- Tabele dla strony serwisu
-- Dodajemy to do bazy danych motoshop_db

-- Tabela mechaników
CREATE TABLE IF NOT EXISTS `mechanics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(200) NOT NULL,
  `experience` int(11) NOT NULL,
  `rating` float NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela usług serwisowych
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'w minutach',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela rezerwacji
CREATE TABLE IF NOT EXISTS `service_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `mechanic_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `mechanic_id` (`mechanic_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela opinii o mechanikach
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mechanic_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text NOT NULL,
  `reviewer_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `mechanic_id` (`mechanic_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Przykładowe dane początkowe dla mechaników
INSERT INTO `mechanics` (`name`, `specialization`, `experience`, `rating`, `description`, `image_path`, `status`) VALUES
('Jan Kowalski', 'Honda, Yamaha', 15, 4.5, 'Doświadczony mechanik z 15-letnim stażem. Specjalizuje się w motocyklach japońskich.', 'assets/images/mechanics/mechanic1.jpg', 'active'),
('Piotr Nowak', 'BMW, Ducati', 10, 5.0, 'Ekspert w motocyklach europejskich. Certyfikowany mechanik BMW i Ducati.', 'assets/images/mechanics/mechanic2.jpg', 'active'),
('Anna Wiśniewska', 'Suzuki, Kawasaki', 5, 4.0, 'Młoda, ambitna mechanik z pasją do motocykli sportowych.', 'assets/images/mechanics/mechanic3.jpg', 'active');

-- Przykładowe dane początkowe dla usług
INSERT INTO `services` (`name`, `description`, `price`, `duration`) VALUES
('Przegląd okresowy', 'Podstawowy przegląd motocykla zgodnie z zaleceniami producenta', 250.00, 120),
('Naprawa', 'Naprawa usterek mechanicznych', 300.00, 180),
('Diagnostyka', 'Pełna diagnostyka komputerowa', 150.00, 60),
('Konserwacja', 'Konserwacja i przygotowanie motocykla do sezonu lub zimowania', 200.00, 90);
