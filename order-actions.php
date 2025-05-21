<?php
require_once 'includes/config.php';

// Sprawdzanie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby wykonać tę akcję']);
    exit;
}

// Sprawdzanie czy żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda żądania']);
    exit;
}

// Sprawdzanie czy przekazano ID zamówienia
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nie podano ID zamówienia']);
    exit;
}

$order_id = (int)$_POST['order_id'];
$user_id = $_SESSION['user_id'];

// Sprawdzanie czy zamówienie należy do zalogowanego użytkownika
$check_query = "SELECT status FROM orders WHERE id = $order_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if (!$check_result || $check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Nie znaleziono zamówienia']);
    exit;
}

$order = $check_result->fetch_assoc();

// Sprawdzanie czy zamówienie może być anulowane
if (!in_array($order['status'], ['pending', 'processing'])) {
    echo json_encode(['success' => false, 'message' => 'Nie można anulować zamówienia w tym statusie']);
    exit;
}

// Rozpoczęcie transakcji
$conn->begin_transaction();

try {
    // Aktualizacja statusu zamówienia
    $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";
    if (!$conn->query($update_query)) {
        throw new Exception('Błąd podczas aktualizacji statusu zamówienia');
    }

    // Przywrócenie stanów magazynowych
    $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id";
    $items_result = $conn->query($items_query);

    if ($items_result && $items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            
            $update_stock = "UPDATE products SET stock = stock + $quantity WHERE id = $product_id";
            if (!$conn->query($update_stock)) {
                throw new Exception('Błąd podczas aktualizacji stanów magazynowych');
            }
        }
    }

    // Zatwierdzenie transakcji
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Zamówienie zostało anulowane']);
} catch (Exception $e) {
    // Wycofanie transakcji w przypadku błędu
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 