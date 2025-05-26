<?php
// Skrypt do inicjalizacji tabeli administratorów i dodania przykładowych kont

// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Tworzenie tabeli administratorów
$create_table_query = "
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` ENUM('admin', 'mechanic', 'owner') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($create_table_query)) {
    echo "Tabela 'admins' została utworzona pomyślnie lub już istniała.<br>";
} else {
    echo "Błąd podczas tworzenia tabeli: " . $conn->error . "<br>";
    exit;
}

// Sprawdzenie, czy istnieje już jakiś administrator
$check_query = "SELECT COUNT(*) as admin_count FROM admins";
$result = $conn->query($check_query);
$admin_exists = false;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['admin_count'] > 0) {
        $admin_exists = true;
    }
}

// Dodawanie przykładowych administratorów, jeśli nie istnieją
if (!$admin_exists) {
    // Hasła dla przykładowych administratorów
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $mechanic_password = password_hash('mechanic123', PASSWORD_DEFAULT);
    $owner_password = password_hash('owner123', PASSWORD_DEFAULT);
    
    // Przykładowy administrator główny
    $insert_admin_query = "
    INSERT INTO admins (username, password, name, email, role) VALUES 
    ('admin', ?, 'Administrator Główny', 'admin@motoshop.pl', 'admin')
    ";
    
    $stmt = $conn->prepare($insert_admin_query);
    $stmt->bind_param("s", $admin_password);
    
    if ($stmt->execute()) {
        echo "Administrator główny został dodany pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania administratora głównego: " . $stmt->error . "<br>";
    }
    
    // Przykładowy mechanik
    $insert_mechanic_query = "
    INSERT INTO admins (username, password, name, email, role) VALUES 
    ('mechanik', ?, 'Jan Kowalski', 'mechanik@motoshop.pl', 'mechanic')
    ";
    
    $stmt = $conn->prepare($insert_mechanic_query);
    $stmt->bind_param("s", $mechanic_password);
    
    if ($stmt->execute()) {
        echo "Mechanik został dodany pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania mechanika: " . $stmt->error . "<br>";
    }
    
    // Przykładowy właściciel
    $insert_owner_query = "
    INSERT INTO admins (username, password, name, email, role) VALUES 
    ('wlasciciel', ?, 'Anna Nowak', 'wlasciciel@motoshop.pl', 'owner')
    ";
    
    $stmt = $conn->prepare($insert_owner_query);
    $stmt->bind_param("s", $owner_password);
    
    if ($stmt->execute()) {
        echo "Właściciel został dodany pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania właściciela: " . $stmt->error . "<br>";
    }
    
    echo "<h3>Przykładowe konta do logowania:</h3>";
    echo "<ul>";
    echo "<li><strong>Administrator:</strong> login: admin, hasło: admin123</li>";
    echo "<li><strong>Mechanik:</strong> login: mechanik, hasło: mechanic123</li>";
    echo "<li><strong>Właściciel:</strong> login: wlasciciel, hasło: owner123</li>";
    echo "</ul>";
} else {
    echo "Administratorzy już istnieją w bazie danych.<br>";
}

echo "<p>Możesz teraz przejść do <a href='login.php'>strony logowania</a> i zalogować się używając jednego z przykładowych kont.</p>";
?>
