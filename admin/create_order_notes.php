<?php
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Tworzenie tabeli order_notes jeśli nie istnieje
$create_table_query = "CREATE TABLE IF NOT EXISTS order_notes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    admin_id INT(11) NOT NULL,
    admin_name VARCHAR(100) NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_table_query) === TRUE) {
    echo "Tabela order_notes została utworzona pomyślnie lub już istniała.<br>";
} else {
    echo "Błąd podczas tworzenia tabeli order_notes: " . $conn->error . "<br>";
}

// Dodanie klucza obcego
$add_foreign_key_query = "ALTER TABLE order_notes
                         ADD CONSTRAINT fk_order_notes_order_id
                         FOREIGN KEY (order_id) REFERENCES orders(id)
                         ON DELETE CASCADE";

// Najpierw sprawdzamy, czy klucz obcy już istnieje
$check_foreign_key_query = "SELECT * 
                           FROM information_schema.TABLE_CONSTRAINTS 
                           WHERE CONSTRAINT_SCHEMA = DATABASE() 
                           AND CONSTRAINT_NAME = 'fk_order_notes_order_id'";

$result = $conn->query($check_foreign_key_query);

if ($result && $result->num_rows == 0) {
    // Klucz obcy nie istnieje, dodajemy go
    if ($conn->query($add_foreign_key_query) === TRUE) {
        echo "Klucz obcy dla tabeli order_notes został dodany pomyślnie.<br>";
    } else {
        echo "Błąd podczas dodawania klucza obcego: " . $conn->error . "<br>";
    }
} else {
    echo "Klucz obcy już istnieje.<br>";
}

echo "<a href='order-details.php?id=" . (isset($_GET['id']) ? $_GET['id'] : '') . "'>Wróć do szczegółów zamówienia</a>";
?>
