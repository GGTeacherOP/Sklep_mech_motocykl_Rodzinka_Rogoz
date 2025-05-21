<?php
require_once 'includes/config.php';

// Zapytanie SQL do aktualizacji tabeli orders
$sql = "ALTER TABLE orders
        ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0.00 AFTER payment_method,
        ADD COLUMN shipping_method VARCHAR(50) AFTER subtotal,
        ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_method,
        ADD COLUMN total DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_cost,
        ADD COLUMN order_date DATETIME DEFAULT CURRENT_TIMESTAMP AFTER status";

// Wykonanie zapytania
if ($conn->query($sql) === TRUE) {
    echo "Tabela orders została zaktualizowana pomyślnie.";
} else {
    echo "Błąd podczas aktualizacji tabeli: " . $conn->error;
}

$conn->close();
?> 