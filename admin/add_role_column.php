<?php
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Sprawdzenie czy kolumna 'role' już istnieje
$check_column_query = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $conn->query($check_column_query);

if ($result && $result->num_rows == 0) {
    // Kolumna nie istnieje, dodajemy ją
    $add_column_query = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'mechanic', 'owner') NOT NULL DEFAULT 'user'";
    
    if ($conn->query($add_column_query)) {
        echo "Kolumna 'role' została dodana do tabeli 'users' pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania kolumny 'role': " . $conn->error . "<br>";
    }
} else {
    echo "Kolumna 'role' już istnieje w tabeli 'users'.<br>";
}

echo "<p>Możesz teraz przejść do <a href='users.php'>zarządzania użytkownikami</a>.</p>";
?>
