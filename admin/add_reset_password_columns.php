<?php
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Sprawdzenie czy kolumny już istnieją
$check_columns_query = "SHOW COLUMNS FROM users LIKE 'reset_token'";
$result = $conn->query($check_columns_query);

if ($result && $result->num_rows == 0) {
    // Kolumny nie istnieją, dodajemy je
    $add_columns_query = "ALTER TABLE users 
                         ADD COLUMN reset_token VARCHAR(64) NULL,
                         ADD COLUMN reset_token_expires DATETIME NULL";
    
    if ($conn->query($add_columns_query)) {
        echo "Kolumny 'reset_token' i 'reset_token_expires' zostały dodane do tabeli 'users' pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania kolumn: " . $conn->error . "<br>";
    }
} else {
    echo "Kolumny 'reset_token' i 'reset_token_expires' już istnieją w tabeli 'users'.<br>";
}

echo "<p>Możesz teraz przejść do <a href='users.php'>zarządzania użytkownikami</a>.</p>";
?> 