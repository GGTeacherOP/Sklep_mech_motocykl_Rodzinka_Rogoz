<?php
// Skrypt dodający kolumnę status do tabeli users
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Sprawdzenie czy kolumna 'status' już istnieje
$check_column_query = "SHOW COLUMNS FROM users LIKE 'status'";
$result = $conn->query($check_column_query);

if ($result && $result->num_rows == 0) {
    // Kolumna nie istnieje, dodajemy ją
    $add_column_query = "ALTER TABLE users ADD COLUMN status ENUM('active', 'blocked') NOT NULL DEFAULT 'active'";
    
    if ($conn->query($add_column_query)) {
        echo "Kolumna 'status' została dodana do tabeli 'users' pomyślnie.<br>";
        
        // Aktualizacja istniejących użytkowników, ustawiając domyślnie 'active'
        $update_query = "UPDATE users SET status = 'active' WHERE status IS NULL";
        if ($conn->query($update_query)) {
            echo "Wszyscy istniejący użytkownicy zostali ustawieni jako 'active'.<br>";
        } else {
            echo "Błąd podczas aktualizacji istniejących użytkowników: " . $conn->error . "<br>";
        }
    } else {
        echo "Błąd podczas dodawania kolumny 'status': " . $conn->error . "<br>";
    }
} else {
    echo "Kolumna 'status' już istnieje w tabeli 'users'.<br>";
}

echo "<p>Możesz teraz przejść do <a href='users.php'>zarządzania użytkownikami</a>.</p>";
?>
