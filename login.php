<?php
// Strona logowania
$page_title = "Logowanie";
require_once 'includes/config.php';

// --- FUNKCJA PRZENOSZĄCA KOSZYK SESYJNY DO KOSZYKA UŻYTKOWNIKA ---
/**
 * Przenosi produkty z koszyka sesyjnego do koszyka zalogowanego użytkownika.
 * 
 * @param int $user_id ID zalogowanego użytkownika.
 * @param mysqli $conn Połączenie z bazą danych.
 */
function transferSessionCartToUserCart($user_id, $conn) {
    // TEMPORARY DEBUG LOG: Check session cart status at the beginning of the function
    error_log("transferSessionCartToUserCart (login.php): Start. Session cart: " . print_r($_SESSION['cart_items'], true));

    // Sprawdzenie czy koszyk sesyjny istnieje i nie jest pusty
    if (isset($_SESSION['cart_items']) && !empty($_SESSION['cart_items'])) {
        // Pobieranie lub tworzenie koszyka użytkownika w bazie danych
        $cart_query = "SELECT id FROM carts WHERE user_id = ?";
        $cart_stmt = $conn->prepare($cart_query);
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        $cart_stmt->close();

        $cart_id = 0;
        if ($cart_result && $cart_result->num_rows > 0) {
            $cart = $cart_result->fetch_assoc();
            $cart_id = $cart['id'];
        } else {
            // Tworzenie nowego koszyka jeśli użytkownik go nie ma
            $insert_cart_query = "INSERT INTO carts (user_id) VALUES (?)";
            $insert_cart_stmt = $conn->prepare($insert_cart_query);
            $insert_cart_stmt->bind_param("i", $user_id);
            if ($insert_cart_stmt->execute()) {
                $cart_id = $conn->insert_id;
            } else {
                 error_log("Błąd tworzenia koszyka użytkownika (login.php): " . $insert_cart_stmt->error);
                 return; // Przerywamy jeśli nie udało się utworzyć koszyka
            }
             $insert_cart_stmt->close();
        }
        
        // Jeśli mamy ID koszyka, przenosimy produkty
        if ($cart_id > 0) {
             // Przenoszenie produktów z sesji do bazy danych
             foreach ($_SESSION['cart_items'] as $session_item) {
                 $product_id = $session_item['product_id'];
                 $quantity = $session_item['quantity'];

                 // Sprawdzenie czy produkt już jest w koszyku użytkownika
                 $check_query = "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
                 $check_stmt = $conn->prepare($check_query);
                 $check_stmt->bind_param("ii", $cart_id, $product_id);
                 $check_stmt->execute();
                 $check_result = $check_stmt->get_result();

                 if ($check_result && $check_result->num_rows > 0) {
                     // Aktualizacja ilości istniejącego produktu
                     $item = $check_result->fetch_assoc();
                     $new_quantity = $item['quantity'] + $quantity;

                     $update_query = "UPDATE cart_items SET quantity = ? WHERE id = ?";
                     $update_stmt = $conn->prepare($update_query);
                     $update_stmt->bind_param("ii", $new_quantity, $item['id']);
                     if (!$update_stmt->execute()) {
                         error_log("Błąd aktualizacji ilości produktu w koszyku (login.php): " . $update_stmt->error);
                     }
                     $update_stmt->close();
                 } else {
                     // Dodanie nowego produktu do koszyka
                     $insert_query = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
                     $insert_stmt = $conn->prepare($insert_query);
                     $insert_stmt->bind_param("iii", $cart_id, $product_id, $quantity);
                     if (!$insert_stmt->execute()) {
                         error_log("Błąd dodawania produktu do koszyka (login.php): " . $insert_stmt->error);
                     }
                     $insert_stmt->close();
                 }
                 $check_stmt->close();
             }
             
             // Czyszczenie koszyka sesyjnego po pomyślnym przeniesieniu
             unset($_SESSION['cart_items']);

             // TEMPORARY DEBUG LOG: Check session cart status after unset
             error_log("transferSessionCartToUserCart (login.php): After unset. Session cart: " . print_r($_SESSION['cart_items'], true));
        }
    }
}

// --- KONIEC FUNKCJI PRZENOSZĄCEJ KOSZYK ---

// Sprawdzenie czy użytkownik jest już zalogowany
if (isLoggedIn()) {
    redirect('account.php');
}

// Obsługa formularza logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // Sprawdzenie czy email i hasło zostały podane
    if (empty($email) || empty($password)) {
        setMessage('Wprowadź email i hasło', 'error');
    } else {
        // Wyszukiwanie użytkownika w bazie danych
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Weryfikacja hasła
            if (password_verify($password, $user['password'])) {
                // Poprawne dane logowania
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Obsługa "zapamiętaj mnie" (w rzeczywistości należałoby użyć cookies)
                if ($remember) {
                    // Tutaj dodać obsługę cookies dla "zapamiętaj mnie"
                }
                
                // --- PRZENIESIENIE KOSZYKA SESYJNEGO DO KOSZYKA UŻYTKOWNIKA ---
                transferSessionCartToUserCart($user['id'], $conn);
                // --- KONIEC PRZENOSZENIA KOSZYKA ---

                // Ustalenie ścieżki przekierowania po zalogowaniu
                $redirect_url = 'account.html'; // Domyślna ścieżka dla użytkownika

                // Sprawdzenie roli użytkownika
                if (isset($user['role']) && in_array($user['role'], ['admin', 'mechanic', 'owner'])) {
                     $redirect_url = 'admin/index.php'; // Domyślna ścieżka dla admina/mechanika/właściciela
                }

                // Sprawdzenie czy w żądaniu jest parametr 'redirect' (np. z modala logowania)
                if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                    $requested_redirect = sanitize($_POST['redirect']);
                     // Lista dozwolonych stron do przekierowania - zabezpieczenie przed otwartym przekierowaniem
                    $allowed_redirects = ['checkout.php', 'cart.php', 'index.php', 'account.html', 'admin/index.php'];
                    if (in_array($requested_redirect, $allowed_redirects)) {
                        $redirect_url = $requested_redirect;
                    }
                } else if (isset($_GET['redirect']) && !empty($_GET['redirect'])) { // Obsługa przekierowania z GET (np. po kliknięciu w link w modalu)
                     $requested_redirect = sanitize($_GET['redirect']);
                     $allowed_redirects = ['checkout.php', 'cart.php', 'index.php', 'account.html', 'admin/index.php'];
                     if (in_array($requested_redirect, $allowed_redirects)) {
                         $redirect_url = $requested_redirect;
                     }
                }
                
                redirect($redirect_url); // Przekierowanie na docelową stronę
            } else {
                // Niepoprawne hasło
                setMessage('Niepoprawny email lub hasło', 'error');
            }
        } else {
            // Użytkownik nie istnieje
            setMessage('Niepoprawny email lub hasło', 'error');
        }
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-center mb-8">Zaloguj się</h1>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-8">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                <div class="flex justify-end mt-2">
                    <a href="reset-password.php" class="text-sm text-primary hover:underline">Zapomniałeś hasła?</a>
                </div>
            </div>

            <div class="flex items-center mb-6">
                <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700">Zapamiętaj mnie</label>
            </div>

            <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition">
                Zaloguj się
            </button>
        </form>

        <div class="relative mb-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500">Lub kontynuuj przez</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <button class="flex items-center justify-center bg-blue-600 text-white py-3 rounded-button hover:bg-opacity-90 transition">
                <i class="ri-facebook-fill mr-2"></i>
                Facebook
            </button>
            <button class="flex items-center justify-center bg-red-500 text-white py-3 rounded-button hover:bg-opacity-90 transition">
                <i class="ri-google-fill mr-2"></i>
                Google
            </button>
        </div>

        <div class="text-center">
            <p class="text-gray-600">
                Nie masz konta? 
                <a href="register.php" class="text-primary font-medium hover:underline">Zarejestruj się</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
