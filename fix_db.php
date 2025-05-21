<?php
// Skrypt naprawiający bazę danych dla strony serwisu

// Dane połączenia
$host = 'localhost';
$username = 'root';
$password = '';
$db_name = 'motoshop_db'; // Zgodnie z config.php

// Nawiązanie połączenia
$conn = new mysqli($host, $username, $password);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

echo "<h2>Naprawianie bazy danych dla strony serwisu</h2>";

// Utworzenie bazy danych, jeśli nie istnieje
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "<p>Baza danych '$db_name' istnieje lub została utworzona pomyślnie.</p>";
} else {
    die("<p>Błąd podczas tworzenia bazy danych: " . $conn->error . "</p>");
}

// Wybór bazy danych
$conn->select_db($db_name);

// Odczytanie zawartości pliku SQL
$sql_content = file_get_contents('service_tables.sql');

// Podzielenie pliku na pojedyncze zapytania
$queries = explode(';', $sql_content);
$success = true;

// Wykonanie każdego zapytania
foreach ($queries as $query) {
    $query = trim($query);
    
    if (empty($query)) {
        continue;
    }
    
    if ($conn->query($query) !== TRUE) {
        echo "<p>Błąd podczas wykonywania zapytania: " . $conn->error . "</p>";
        echo "<pre>" . htmlspecialchars($query) . "</pre>";
        $success = false;
    }
}

if ($success) {
    echo "<p>Wszystkie tabele zostały utworzone pomyślnie!</p>";
    echo "<p>Strona serwisu powinna teraz działać poprawnie.</p>";
    echo "<p><a href='service.php' style='background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Przejdź do strony serwisu</a></p>";
} else {
    echo "<p>Wystąpiły błędy podczas importowania struktury bazy danych.</p>";
}

$conn->close();
?>
