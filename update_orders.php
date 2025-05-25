<?php
require_once 'includes/config.php';

// Aktualizacja total_amount dla wszystkich zamówień
$update_query = "UPDATE orders SET total_amount = subtotal + shipping_cost WHERE total_amount = 0.00";
if ($conn->query($update_query)) {
    echo "Zaktualizowano " . $conn->affected_rows . " zamówień.";
} else {
    echo "Błąd podczas aktualizacji: " . $conn->error;
}

$conn->close();
?> 