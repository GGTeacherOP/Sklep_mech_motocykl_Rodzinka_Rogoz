<?php
// Plik obsługujący akcje związane z listą życzeń (dodawanie/usuwanie produktów)
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    // Jeśli nie jest zalogowany, przekieruj go do strony logowania
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby korzystać z listy życzeń.', 'redirect' => 'login.php']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Wystąpił błąd. Spróbuj ponownie.'];

// Sprawdzenie czy użytkownik ma już listę życzeń
$wishlist_stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ?");
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

$wishlist_id = null;
if ($wishlist_result->num_rows > 0) {
    $wishlist = $wishlist_result->fetch_assoc();
    $wishlist_id = $wishlist['id'];
} else {
    // Tworzenie nowej listy życzeń dla użytkownika
    $create_wishlist_stmt = $conn->prepare("INSERT INTO wishlists (user_id) VALUES (?)");
    $create_wishlist_stmt->bind_param("i", $user_id);
    
    if ($create_wishlist_stmt->execute()) {
        $wishlist_id = $conn->insert_id;
    }
    $create_wishlist_stmt->close();
}
$wishlist_stmt->close();

// Jeśli mamy prawidłowy wishlist_id, obsłuż odpowiednie akcje
if ($wishlist_id) {
    // Akcja dodawania produktu do listy życzeń
    if (isset($_POST['action']) && $_POST['action'] == 'add' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        
        // Sprawdzenie czy produkt już jest na liście życzeń
        $check_stmt = $conn->prepare("SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $wishlist_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $response = ['success' => true, 'message' => 'Produkt jest już na Twojej liście życzeń.'];
        } else {
            // Dodawanie produktu do listy życzeń
            $add_stmt = $conn->prepare("INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)");
            $add_stmt->bind_param("ii", $wishlist_id, $product_id);
            
            if ($add_stmt->execute()) {
                $response = [
                    'success' => true, 
                    'message' => 'Produkt został dodany do Twojej listy życzeń.',
                    'wishlist_count' => getWishlistCount($conn, $wishlist_id)
                ];
            }
            $add_stmt->close();
        }
        $check_stmt->close();
    }
    
    // Akcja usuwania produktu z listy życzeń
    elseif (isset($_POST['action']) && $_POST['action'] == 'remove' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        
        $remove_stmt = $conn->prepare("DELETE FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
        $remove_stmt->bind_param("ii", $wishlist_id, $product_id);
        
        if ($remove_stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => 'Produkt został usunięty z Twojej listy życzeń.',
                'wishlist_count' => getWishlistCount($conn, $wishlist_id)
            ];
        }
        $remove_stmt->close();
    }
    
    // Akcja przeniesienia produktu do koszyka
    elseif (isset($_POST['action']) && $_POST['action'] == 'move_to_cart' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        
        // Dodanie produktu do koszyka
        $success = addToCart($conn, $user_id, $product_id);
        
        if ($success) {
            // Usunięcie z listy życzeń
            $remove_stmt = $conn->prepare("DELETE FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
            $remove_stmt->bind_param("ii", $wishlist_id, $product_id);
            $remove_stmt->execute();
            $remove_stmt->close();
            
            $response = [
                'success' => true, 
                'message' => 'Produkt został przeniesiony do koszyka.',
                'wishlist_count' => getWishlistCount($conn, $wishlist_id)
            ];
        }
    }
}

// Zwracanie odpowiedzi w formacie JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;

// Funkcja sprawdzająca liczbę produktów na liście życzeń
function getWishlistCount($conn, $wishlist_id) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist_items WHERE wishlist_id = ?");
    $count_stmt->bind_param("i", $wishlist_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $count_stmt->close();
    return $count_data['count'];
}

// Funkcja dodająca produkt do koszyka
function addToCart($conn, $user_id, $product_id) {
    // Sprawdzenie czy użytkownik ma już koszyk
    $cart_stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $cart_id = null;
    if ($cart_result->num_rows > 0) {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
    } else {
        // Tworzenie nowego koszyka dla użytkownika
        $create_cart_stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $create_cart_stmt->bind_param("i", $user_id);
        if ($create_cart_stmt->execute()) {
            $cart_id = $conn->insert_id;
        }
        $create_cart_stmt->close();
    }
    $cart_stmt->close();
    
    if ($cart_id) {
        // Sprawdzenie czy produkt już jest w koszyku
        $check_cart_stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $check_cart_stmt->bind_param("ii", $cart_id, $product_id);
        $check_cart_stmt->execute();
        $check_cart_result = $check_cart_stmt->get_result();
        
        if ($check_cart_result->num_rows > 0) {
            // Zwiększenie ilości
            $cart_item = $check_cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + 1;
            
            $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            $success = $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Dodanie nowego produktu do koszyka
            $add_cart_stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)");
            $add_cart_stmt->bind_param("ii", $cart_id, $product_id);
            $success = $add_cart_stmt->execute();
            $add_cart_stmt->close();
        }
        $check_cart_stmt->close();
        
        return $success;
    }
    
    return false;
}
?>
