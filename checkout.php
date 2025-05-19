<?php
$page_title = "Zamówienie | MotoShop";
require_once 'includes/config.php';
include 'includes/header.php';

// Sprawdzenie czy koszyk nie jest pusty
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

// Pobieranie zawartości koszyka
if (isLoggedIn()) {
    // Dla zalogowanego użytkownika - pobieranie z bazy danych
    $user_id = $_SESSION['user_id'];
    
    // Pobieranie ID koszyka użytkownika
    $cart_query = "SELECT id FROM carts WHERE user_id = $user_id";
    $cart_result = $conn->query($cart_query);
    
    if ($cart_result && $cart_result->num_rows > 0) {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['id'];
        
        // Pobieranie produktów z koszyka
        $items_query = "SELECT ci.*, p.name, p.slug, p.price, p.sale_price, p.stock 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.id 
                        WHERE ci.cart_id = $cart_id";
        $items_result = $conn->query($items_query);
        
        if ($items_result && $items_result->num_rows > 0) {
            while ($item = $items_result->fetch_assoc()) {
                $cart_items[] = $item;
                $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                $cart_total += $price * $item['quantity'];
                $cart_count += $item['quantity'];
            }
        }
    }
    
    // Pobieranie danych użytkownika
    $user_query = "SELECT * FROM users WHERE id = $user_id";
    $user_result = $conn->query($user_query);
    $user_data = $user_result->fetch_assoc();
} else {
    // Dla niezalogowanego użytkownika - pobieranie z sesji
    if (isset($_SESSION['cart_items']) && !empty($_SESSION['cart_items'])) {
        $session_items = $_SESSION['cart_items'];
        
        // Pobieranie szczegółów produktów z bazy danych
        foreach ($session_items as $session_item) {
            $product_id = $session_item['product_id'];
            $product_query = "SELECT * FROM products WHERE id = $product_id AND status = 'published'";
            $product_result = $conn->query($product_query);
            
            if ($product_result && $product_result->num_rows > 0) {
                $product = $product_result->fetch_assoc();
                $item = [
                    'product_id' => $product_id,
                    'quantity' => $session_item['quantity'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'price' => $product['price'],
                    'sale_price' => $product['sale_price'],
                    'stock' => $product['stock']
                ];
                
                $cart_items[] = $item;
                $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                $cart_total += $price * $session_item['quantity'];
                $cart_count += $session_item['quantity'];
            }
        }
    }
    
    $user_data = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'postal_code' => ''
    ];
}

// Przekierowanie do koszyka, jeśli jest pusty
if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// Obliczanie wartości zamówienia
$subtotal = $cart_total;
$shipping_cost = 15.00; // Domyślny koszt wysyłki
$total = $subtotal + $shipping_cost;

// Obsługa formularza zamówienia
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Walidacja danych
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitize($_POST['city']) : '';
    $postal_code = isset($_POST['postal_code']) ? sanitize($_POST['postal_code']) : '';
    $shipping_method = isset($_POST['shipping_method']) ? sanitize($_POST['shipping_method']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : '';
    
    // Sprawdzanie czy wszystkie pola są wypełnione
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || 
        empty($address) || empty($city) || empty($postal_code) || 
        empty($shipping_method) || empty($payment_method)) {
        $error_message = 'Wszystkie pola są wymagane.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Podany adres email jest nieprawidłowy.';
    } else {
        // Aktualizacja kosztu wysyłki na podstawie wybranej metody
        if ($shipping_method === 'courier') {
            $shipping_cost = 15.00;
        } elseif ($shipping_method === 'inpost') {
            $shipping_cost = 12.00;
        } elseif ($shipping_method === 'pickup') {
            $shipping_cost = 0;
        }
        
        $total = $subtotal + $shipping_cost;
        
        // Tworzenie zamówienia w bazie danych
        $status = 'pending';
        $order_number = generateOrderNumber();
        $order_date = date('Y-m-d H:i:s');
        
        // Zapisywanie zamówienia
        $order_query = "INSERT INTO orders (order_number, user_id, first_name, last_name, email, phone, 
                        address, city, postal_code, shipping_method, payment_method, 
                        subtotal, shipping_cost, total, status, order_date) 
                        VALUES ('$order_number', " . (isLoggedIn() ? $user_id : "NULL") . ", 
                        '$first_name', '$last_name', '$email', '$phone', 
                        '$address', '$city', '$postal_code', '$shipping_method', '$payment_method', 
                        $subtotal, $shipping_cost, $total, '$status', '$order_date')";
        
        if ($conn->query($order_query) === TRUE) {
            $order_id = $conn->insert_id;
            
            // Zapisywanie pozycji zamówienia
            $success = true;
            
            foreach ($cart_items as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                               VALUES ($order_id, $product_id, $quantity, $price)";
                
                if ($conn->query($item_query) !== TRUE) {
                    $success = false;
                    break;
                }
                
                // Aktualizacja stanu magazynowego
                $stock_query = "UPDATE products SET stock = stock - $quantity WHERE id = $product_id";
                $conn->query($stock_query);
            }
            
            if ($success) {
                // Czyszczenie koszyka po złożeniu zamówienia
                if (isLoggedIn()) {
                    $clear_cart = "DELETE FROM cart_items WHERE cart_id = $cart_id";
                    $conn->query($clear_cart);
                } else {
                    unset($_SESSION['cart_items']);
                }
                
                // Przekierowanie do strony potwierdzenia zamówienia
                header("Location: order-confirmation.php?order_id=$order_id");
                exit;
            } else {
                $error_message = 'Wystąpił błąd podczas tworzenia zamówienia. Spróbuj ponownie.';
            }
        } else {
            $error_message = 'Wystąpił błąd podczas tworzenia zamówienia. Spróbuj ponownie.';
        }
    }
}

// Funkcja do generowania numeru zamówienia
function generateOrderNumber() {
    return 'MS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-3">Zamówienie</h1>
                <nav class="flex mb-8">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-700 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <a href="cart.php" class="text-gray-700 hover:text-primary">
                                    Koszyk
                                </a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <span class="text-primary font-medium">Zamówienie</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                
                <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 text-red-800 rounded-lg p-4 mb-6">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="flex flex-col lg:flex-row gap-8">
                    <div class="lg:w-8/12">
                        <!-- Dane zamawiającego -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                            <h2 class="text-lg font-semibold mb-4">Dane zamawiającego</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Imię *</label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo $user_data['first_name']; ?>" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko *</label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo $user_data['last_name']; ?>" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" id="email" name="email" value="<?php echo $user_data['email']; ?>" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo $user_data['phone']; ?>" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Adres dostawy -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                            <h2 class="text-lg font-semibold mb-4">Adres dostawy</h2>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adres *</label>
                                    <input type="text" id="address" name="address" value="<?php echo $user_data['address']; ?>" required
                                        class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Miejscowość *</label>
                                        <input type="text" id="city" name="city" value="<?php echo $user_data['city']; ?>" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Kod pocztowy *</label>
                                        <input type="text" id="postal_code" name="postal_code" value="<?php echo $user_data['postal_code']; ?>" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wybór dostawy -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                            <h2 class="text-lg font-semibold mb-4">Metoda dostawy</h2>
                            
                            <div class="space-y-4">
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer shipping-option" data-cost="15.00">
                                    <input type="radio" id="shipping_courier" name="shipping_method" value="courier" checked
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="shipping_courier" class="flex flex-1 ml-3 cursor-pointer">
                                        <div>
                                            <span class="block font-medium text-gray-900">Kurier</span>
                                            <span class="block text-sm text-gray-500">Dostawa w ciągu 1-2 dni roboczych</span>
                                        </div>
                                        <span class="ml-auto font-semibold">15,00 zł</span>
                                    </label>
                                </div>
                                
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer shipping-option" data-cost="12.00">
                                    <input type="radio" id="shipping_inpost" name="shipping_method" value="inpost"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="shipping_inpost" class="flex flex-1 ml-3 cursor-pointer">
                                        <div>
                                            <span class="block font-medium text-gray-900">InPost Paczkomat</span>
                                            <span class="block text-sm text-gray-500">Dostawa do paczkomatu w ciągu 1-2 dni roboczych</span>
                                        </div>
                                        <span class="ml-auto font-semibold">12,00 zł</span>
                                    </label>
                                </div>
                                
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer shipping-option" data-cost="0">
                                    <input type="radio" id="shipping_pickup" name="shipping_method" value="pickup"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="shipping_pickup" class="flex flex-1 ml-3 cursor-pointer">
                                        <div>
                                            <span class="block font-medium text-gray-900">Odbiór osobisty</span>
                                            <span class="block text-sm text-gray-500">Sklep MotoShop, ul. Motorowa 123, Warszawa</span>
                                        </div>
                                        <span class="ml-auto font-semibold">0,00 zł</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wybór płatności -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Metoda płatności</h2>
                            
                            <div class="space-y-4">
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer">
                                    <input type="radio" id="payment_online" name="payment_method" value="online" checked
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="payment_online" class="ml-3 flex items-center cursor-pointer">
                                        <span class="font-medium text-gray-900">Płatność online (Przelewy24)</span>
                                        <img src="assets/images/payment-icons.png" alt="Metody płatności" class="h-8 ml-3">
                                    </label>
                                </div>
                                
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer">
                                    <input type="radio" id="payment_card" name="payment_method" value="card"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="payment_card" class="ml-3 flex items-center cursor-pointer">
                                        <span class="font-medium text-gray-900">Karta płatnicza</span>
                                        <img src="assets/images/cards.png" alt="Karty płatnicze" class="h-6 ml-3">
                                    </label>
                                </div>
                                
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer">
                                    <input type="radio" id="payment_cash" name="payment_method" value="cash"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="payment_cash" class="ml-3 cursor-pointer">
                                        <span class="font-medium text-gray-900">Płatność przy odbiorze</span>
                                    </label>
                                </div>
                                
                                <div class="flex items-center border border-gray-200 rounded-lg p-4 cursor-pointer">
                                    <input type="radio" id="payment_transfer" name="payment_method" value="transfer"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <label for="payment_transfer" class="ml-3 cursor-pointer">
                                        <span class="font-medium text-gray-900">Przelew tradycyjny</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:w-4/12">
                        <!-- Podsumowanie zamówienia -->
                        <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                            <h2 class="text-lg font-semibold mb-4">Twoje zamówienie</h2>
                            
                            <div class="border-b border-gray-200 pb-4 mb-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Produkty (<?php echo $cart_count; ?>)</h3>
                                
                                <div class="space-y-3">
                                    <?php foreach ($cart_items as $item): 
                                        $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                                        $item_total = $price * $item['quantity'];
                                    ?>
                                    <div class="flex justify-between text-sm">
                                        <span><?php echo $item['name']; ?> <span class="text-gray-500">x <?php echo $item['quantity']; ?></span></span>
                                        <span class="font-medium"><?php echo number_format($item_total, 2, ',', ' '); ?> zł</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Wartość produktów</span>
                                    <span class="font-medium"><?php echo number_format($subtotal, 2, ',', ' '); ?> zł</span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Koszt dostawy</span>
                                    <span class="font-medium" id="shipping-cost"><?php echo number_format($shipping_cost, 2, ',', ' '); ?> zł</span>
                                </div>
                                
                                <div class="flex justify-between items-center pt-3 border-t border-gray-200 text-lg font-bold">
                                    <span>Razem</span>
                                    <span class="text-primary" id="total-amount"><?php echo number_format($total, 2, ',', ' '); ?> zł</span>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" id="terms" name="terms" required
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                                        Akceptuję <a href="terms.php" class="text-primary hover:underline">regulamin</a> sklepu
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="privacy" name="privacy" required
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                    <label for="privacy" class="ml-2 block text-sm text-gray-900">
                                        Zapoznałem się z <a href="privacy.php" class="text-primary hover:underline">polityką prywatności</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-primary text-white py-3 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                                Złóż zamówienie
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
// Skrypt JS do obsługi zmiany metody dostawy
$extra_js = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const shippingOptions = document.querySelectorAll('.shipping-option');
    const shippingCostElem = document.getElementById('shipping-cost');
    const totalAmountElem = document.getElementById('total-amount');
    const subtotal = $subtotal;
    
    // Obsługa kliknięcia w opcję dostawy
    shippingOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        
        option.addEventListener('click', function() {
            // Zaznacz radio button
            radio.checked = true;
            
            // Pobierz koszt dostawy z atrybutu data-cost
            const shippingCost = parseFloat(this.getAttribute('data-cost'));
            
            // Aktualizuj wyświetlanie kosztu dostawy
            shippingCostElem.textContent = shippingCost.toFixed(2).replace('.', ',') + ' zł';
            
            // Oblicz nową sumę
            const total = subtotal + shippingCost;
            
            // Aktualizuj wyświetlanie sumy
            totalAmountElem.textContent = total.toFixed(2).replace('.', ',') + ' zł';
        });
    });
});
</script>
EOT;

include 'includes/footer.php';
?>
