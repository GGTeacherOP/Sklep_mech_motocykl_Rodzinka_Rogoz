<?php
// Plik obsługujący operacje na koszyku
require_once 'includes/config.php';

// Sprawdzenie czy żądanie wymaga przekierowania czy odpowiedzi JSON
$redirect_after = isset($_POST['redirect']) && $_POST['redirect'] == 'true';

if (!$redirect_after) {
    // Ustawienie nagłówka dla odpowiedzi JSON tylko gdy nie przekierowujemy
    header('Content-Type: application/json');
}

// Sprawdzanie czy żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda żądania']);
    exit;
}

// Pobieranie akcji i sprawdzanie czy jest ustawiona
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Nie określono akcji']);
    exit;
}

// Obsługa różnych akcji
switch ($action) {
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCart();
        break;
    case 'remove':
        removeFromCart();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowa akcja']);
        exit;
}

// Funkcja dodająca produkt do koszyka
function addToCart() {
    global $conn;
    
    // Pobieranie parametrów
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Walidacja
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy identyfikator produktu']);
        exit;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ilość musi być większa od zera']);
        exit;
    }
    
    // Sprawdzanie czy produkt istnieje i jest dostępny
    $product_query = "SELECT * FROM products WHERE id = $product_id AND status = 'published'";
    $product_result = $conn->query($product_query);
    
    if (!$product_result || $product_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Produkt nie istnieje lub jest niedostępny']);
        exit;
    }
    
    $product = $product_result->fetch_assoc();
    
    // Sprawdzanie dostępności produktu (stock)
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Niewystarczająca ilość produktu w magazynie']);
        exit;
    }
    
    // Obsługa koszyka dla zalogowanego użytkownika
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Sprawdzanie czy użytkownik ma już koszyk
        $cart_query = "SELECT id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_query);
        
        $cart_id = 0;
        
        if ($cart_result && $cart_result->num_rows > 0) {
            $cart = $cart_result->fetch_assoc();
            $cart_id = $cart['id'];
        } else {
            // Tworzenie nowego koszyka
            $insert_cart = "INSERT INTO carts (user_id) VALUES ($user_id)";
            if ($conn->query($insert_cart) === TRUE) {
                $cart_id = $conn->insert_id;
            } else {
                echo json_encode(['success' => false, 'message' => 'Błąd podczas tworzenia koszyka']);
                exit;
            }
        }
        
        // Sprawdzanie czy produkt jest już w koszyku
        $item_query = "SELECT * FROM cart_items WHERE cart_id = $cart_id AND product_id = $product_id";
        $item_result = $conn->query($item_query);
        
        if ($item_result && $item_result->num_rows > 0) {
            // Aktualizacja ilości istniejącego produktu
            $item = $item_result->fetch_assoc();
            $new_quantity = $item['quantity'] + $quantity;
            
            // Sprawdzenie czy nowa ilość nie przekracza dostępnej ilości
            if ($new_quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Niewystarczająca ilość produktu w magazynie']);
                exit;
            }
            
            $update_item = "UPDATE cart_items SET quantity = $new_quantity WHERE id = " . $item['id'];
            
            if ($conn->query($update_item) !== TRUE) {
                echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji ilości produktu']);
                exit;
            }
        } else {
            // Dodanie nowego produktu do koszyka
            $insert_item = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES ($cart_id, $product_id, $quantity)";
            
            if ($conn->query($insert_item) !== TRUE) {
                echo json_encode(['success' => false, 'message' => 'Błąd podczas dodawania produktu do koszyka']);
                exit;
            }
        }
        
        // Pobieranie liczby produktów w koszyku
        $count_query = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = $cart_id";
        $count_result = $conn->query($count_query);
        $cart_count = 0;
        
        if ($count_result && $count_result->num_rows > 0) {
            $count_row = $count_result->fetch_assoc();
            $cart_count = (int)$count_row['total'];
        }
        
        echo json_encode(['success' => true, 'message' => 'Produkt dodany do koszyka', 'cart_count' => $cart_count]);
    } else {
        // Obsługa koszyka dla niezalogowanego użytkownika (sesja)
        if (!isset($_SESSION['cart_items'])) {
            $_SESSION['cart_items'] = [];
        }
        
        $cart_items = &$_SESSION['cart_items'];
        $product_price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
        
        // Sprawdzanie czy produkt jest już w koszyku i aktualizacja ilości lub dodanie nowego
        $item_index = -1;
        $total_quantity = $quantity; // Początkowa ilość do dodania
        
        // Przechodzimy przez koszyk, sumujemy ilości i znajdujemy pierwsze wystąpienie
        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                if ($item_index === -1) {
                    $item_index = $key; // Zapamiętaj index pierwszego wystąpienia
                }
                $total_quantity += $item['quantity']; // Sumuj ilości
            }
        }
        
        // Sprawdzenie czy łączna ilość nie przekracza dostępnej ilości
        if ($total_quantity > $product['stock']) {
             echo json_encode(['success' => false, 'message' => 'Niewystarczająca ilość produktu w magazynie']);
             exit;
        }

        // Usuwamy wszystkie wystąpienia danego produktu przed dodaniem/aktualizacją
        $updated_cart_items = [];
        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] != $product_id) {
                $updated_cart_items[] = $item;
            }
        }
        $cart_items = $updated_cart_items;

        // Dodajemy produkt z zaktualizowaną (lub początkową) ilością
        $cart_items[] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product_price,
            'quantity' => $total_quantity,
            'image' => $product['image'] ?? null,
            'stock' => $product['stock']
        ];
        
        // Aktualizacja sesji
        $_SESSION['cart_items'] = $cart_items;
        
        // Obliczanie liczby produktów w koszyku po zaktualizowaniu sesji
        $cart_count = 0;
        foreach ($cart_items as $item) {
            $cart_count += $item['quantity'];
        }
        
        // Sprawdzamy czy mamy przekierować czy zwrócić JSON
        global $redirect_after;
        
        if ($redirect_after) {
            // Przekierowanie do koszyka
            $_SESSION['cart_message'] = 'Produkt został dodany do koszyka';
            header('Location: cart.php');
            exit;
        } else {
            // Zwracamy JSON dla akcji AJAX
            echo json_encode(['success' => true, 'message' => 'Produkt dodany do koszyka', 'cart_count' => $cart_count]);
        }
    }
}

// Funkcja aktualizująca ilość produktu w koszyku
function updateCart() {
    global $conn;
    
    // Pobieranie parametrów
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Walidacja
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy identyfikator produktu']);
        exit;
    }
    
    if ($quantity <= 0) {
        // Jeśli ilość jest mniejsza lub równa zero, usuń produkt z koszyka
        removeFromCart();
        exit;
    }
    
    // Sprawdzanie czy produkt istnieje i jest dostępny
    $product_query = "SELECT * FROM products WHERE id = $product_id AND status = 'published'";
    $product_result = $conn->query($product_query);
    
    if (!$product_result || $product_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Produkt nie istnieje lub jest niedostępny']);
        exit;
    }
    
    $product = $product_result->fetch_assoc();
    
    // Sprawdzanie dostępności produktu (stock)
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Niewystarczająca ilość produktu w magazynie']);
        exit;
    }
    
    // Obsługa koszyka dla zalogowanego użytkownika
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Pobieranie id koszyka
        $cart_query = "SELECT id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_query);
        
        if (!$cart_result || $cart_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Koszyk nie istnieje']);
            exit;
        }
        
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
        
        // Aktualizacja ilości produktu
        $update_item = "UPDATE cart_items SET quantity = $quantity WHERE cart_id = $cart_id AND product_id = $product_id";
        
        if ($conn->query($update_item) !== TRUE) {
            echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji ilości produktu']);
            exit;
        }
        
        // Pobieranie zaktualizowanej liczby produktów w koszyku
        $count_query = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = $cart_id";
        $count_result = $conn->query($count_query);
        $cart_count = 0;
        
        if ($count_result && $count_result->num_rows > 0) {
            $count_row = $count_result->fetch_assoc();
            $cart_count = (int)$count_row['total'];
        }
        
        echo json_encode(['success' => true, 'message' => 'Ilość produktu zaktualizowana', 'cart_count' => $cart_count]);
    } else {
        // Obsługa koszyka dla niezalogowanego użytkownika (sesja)
        if (!isset($_SESSION['cart_items'])) {
            echo json_encode(['success' => false, 'message' => 'Koszyk jest pusty']);
            exit;
        }
        
        $cart_items = &$_SESSION['cart_items'];
        $item_exists = false;
        
        foreach ($cart_items as &$item) {
            if ($item['product_id'] == $product_id) {
                $item_exists = true;
                $item['quantity'] = $quantity;
                break;
            }
        }
        
        if (!$item_exists) {
            echo json_encode(['success' => false, 'message' => 'Produkt nie istnieje w koszyku']);
            exit;
        }
        
        // Obliczanie zaktualizowanej liczby produktów w koszyku
        $cart_count = 0;
        foreach ($cart_items as $item) {
            $cart_count += $item['quantity'];
        }
        
        echo json_encode(['success' => true, 'message' => 'Ilość produktu zaktualizowana', 'cart_count' => $cart_count]);
    }
}

// Funkcja usuwająca produkt z koszyka
function removeFromCart() {
    global $conn;
    
    // Pobieranie parametrów
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    // Walidacja
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowy identyfikator produktu']);
        exit;
    }
    
    // Obsługa koszyka dla zalogowanego użytkownika
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Pobieranie id koszyka
        $cart_query = "SELECT id FROM carts WHERE user_id = $user_id";
        $cart_result = $conn->query($cart_query);
        
        if (!$cart_result || $cart_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Koszyk nie istnieje']);
            exit;
        }
        
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
        
        // Usuwanie produktu z koszyka
        $delete_item = "DELETE FROM cart_items WHERE cart_id = $cart_id AND product_id = $product_id";
        
        if ($conn->query($delete_item) !== TRUE) {
            echo json_encode(['success' => false, 'message' => 'Błąd podczas usuwania produktu z koszyka']);
            exit;
        }
        
        // Pobieranie zaktualizowanej liczby produktów i wartości koszyka
        $cart_query = "SELECT SUM(ci.quantity) as total_count, 
                      SUM(CASE WHEN p.sale_price > 0 THEN p.sale_price * ci.quantity ELSE p.price * ci.quantity END) as total_amount
                      FROM cart_items ci 
                      JOIN products p ON ci.product_id = p.id 
                      WHERE ci.cart_id = $cart_id";
        $cart_result = $conn->query($cart_query);
        $cart_count = 0;
        $cart_total = 0;
        
        if ($cart_result && $cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $cart_count = (int)$cart_row['total_count'];
            $cart_total = (float)$cart_row['total_amount'];
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Produkt usunięty z koszyka', 
            'cart_count' => $cart_count,
            'cart_total' => number_format($cart_total, 2, ',', ' '),
            'final_total' => number_format($cart_total, 2, ',', ' ')
        ]);
    } else {
        // Obsługa koszyka dla niezalogowanego użytkownika (sesja)
        if (!isset($_SESSION['cart_items'])) {
            echo json_encode(['success' => false, 'message' => 'Koszyk jest pusty']);
            exit;
        }
        
        $cart_items = &$_SESSION['cart_items'];
        $updated_cart = [];
        $cart_total = 0;
        $cart_count = 0;
        
        foreach ($cart_items as $item) {
            if ($item['product_id'] != $product_id) {
                $updated_cart[] = $item;
                $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                $cart_total += $price * $item['quantity'];
                $cart_count += $item['quantity'];
            }
        }
        
        $_SESSION['cart_items'] = $updated_cart;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Produkt usunięty z koszyka', 
            'cart_count' => $cart_count,
            'cart_total' => number_format($cart_total, 2, ',', ' '),
            'final_total' => number_format($cart_total, 2, ',', ' ')
        ]);
    }
}
