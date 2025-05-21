<?php
$page_title = "Koszyk | MotoShop";
require_once 'includes/config.php';
include 'includes/header.php';

// Inicjalizacja zmiennych
$cart_items = [];
$cart_total = 0;
$cart_count = 0;
$error_message = '';

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
        $items_query = "SELECT ci.*, p.name, p.slug, p.price, p.sale_price, p.stock, 
                        pi.image_path 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.id 
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
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
} else {
    // Dla niezalogowanego użytkownika - pobieranie z sesji
    if (isset($_SESSION['cart_items']) && !empty($_SESSION['cart_items'])) {
        $session_items = $_SESSION['cart_items'];
        
        // Pobieranie szczegółów produktów z bazy danych
        foreach ($session_items as $session_item) {
            $product_id = $session_item['product_id'];
            $product_query = "SELECT p.*, pi.image_path 
                            FROM products p 
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                            WHERE p.id = $product_id AND p.status = 'published'";
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
                    'stock' => $product['stock'],
                    'image_path' => $product['image_path']
                ];
                
                $cart_items[] = $item;
                $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                $cart_total += $price * $session_item['quantity'];
                $cart_count += $session_item['quantity'];
            }
        }
    }
}

// Obsługa kuponów (prosta implementacja)
$coupon_discount = 0;
$coupon_code = '';

if (isset($_POST['apply_coupon']) && !empty($_POST['coupon_code'])) {
    $coupon_code = sanitize($_POST['coupon_code']);
    
    // Sprawdzanie ważności kuponu (w rzeczywistym przypadku, sprawdzanie w bazie danych)
    $coupon_query = "SELECT * FROM coupons WHERE code = '$coupon_code' AND status = 'active' AND expiry_date >= CURDATE()";
    $coupon_result = $conn->query($coupon_query);
    
    if ($coupon_result && $coupon_result->num_rows > 0) {
        $coupon = $coupon_result->fetch_assoc();
        
        // Obliczanie wysokości rabatu
        if ($coupon['type'] == 'percentage') {
            $coupon_discount = $cart_total * ($coupon['value'] / 100);
        } else {
            $coupon_discount = min($coupon['value'], $cart_total); // Nie można mieć większego rabatu niż kwota koszyka
        }
    } else {
        $error_message = 'Podany kod kuponu jest nieprawidłowy lub wygasł.';
    }
}

// Obliczanie końcowej wartości
$final_total = $cart_total - $coupon_discount;
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-3">Koszyk</h1>
                <nav class="flex">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-700 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <span class="text-primary font-medium">Koszyk</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if (empty($cart_items)): ?>
            <!-- Pusty koszyk -->
            <div class="bg-white rounded-lg p-8 text-center max-w-2xl mx-auto shadow-sm">
                <div class="text-gray-500 mb-4 text-5xl">
                    <i class="ri-shopping-cart-line"></i>
                </div>
                <h2 class="text-2xl font-semibold mb-3">Twój koszyk jest pusty</h2>
                <p class="text-gray-600 mb-8">Wygląda na to, że nie dodałeś jeszcze żadnych produktów do koszyka.</p>
                <a href="ProductCatalog.php" class="inline-block bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                    Przeglądaj produkty
                </a>
            </div>
            <?php else: ?>
            <!-- Zawartość koszyka -->
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-8/12">
                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-50 text-red-800 rounded-lg p-4 mb-6">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="cart-table-container">
                            <table class="cart-table min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Produkt
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cena
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ilość
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Wartość
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <span class="sr-only">Akcje</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($cart_items as $item): 
                                        $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                                        $item_total = $price * $item['quantity'];
                                        $image = $item['image_path'] ?? 'assets/images/placeholder.jpg';
                                    ?>
                                    <tr class="hover:bg-gray-50" data-product-id="<?php echo $item['product_id']; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-14 w-14">
                                                    <img class="h-14 w-14 rounded object-cover" src="<?php echo $image; ?>" alt="<?php echo $item['name']; ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <a href="product.php?slug=<?php echo $item['slug']; ?>" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                        <?php echo $item['name']; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($item['sale_price'])): ?>
                                            <div class="text-sm font-medium text-primary price-value" data-price="<?php echo $item['sale_price']; ?>"><?php echo number_format($item['sale_price'], 2, ',', ' '); ?> zł</div>
                                            <div class="text-xs text-gray-500 line-through"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                            <?php else: ?>
                                            <div class="text-sm font-medium text-gray-900 price-value" data-price="<?php echo $item['price']; ?>"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center border rounded">
                                                <button class="px-3 py-1 text-gray-600 hover:text-primary decrement-qty">
                                                    <i class="ri-subtract-line"></i>
                                                </button>
                                                <input type="number" min="1" max="<?php echo $item['stock']; ?>" value="<?php echo $item['quantity']; ?>" 
                                                    class="w-12 text-center border-0 focus:ring-0 quantity-input"
                                                    data-product-id="<?php echo $item['product_id']; ?>">
                                                <button class="px-3 py-1 text-gray-600 hover:text-primary increment-qty">
                                                    <i class="ri-add-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary item-total">
                                            <?php echo number_format($item_total, 2, ',', ' '); ?> zł
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button class="text-red-600 hover:text-red-800 remove-item" data-product-id="<?php echo $item['product_id']; ?>">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-between">
                        <a href="ProductCatalog.php" class="inline-flex items-center text-primary hover:underline">
                            <i class="ri-arrow-left-line mr-2"></i> Kontynuuj zakupy
                        </a>
                    </div>
                </div>
                
                <div class="lg:w-4/12">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Podsumowanie zamówienia</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Wartość produktów (<span class="cart-count"><?php echo $cart_count; ?></span>)</span>
                                <span class="font-medium cart-total"><?php echo number_format($cart_total, 2, ',', ' '); ?> zł</span>
                            </div>
                            
                            <?php if ($coupon_discount > 0): ?>
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200 text-green-600">
                                <span>Rabat kuponowy</span>
                                <span class="coupon-discount" data-discount="<?php echo $coupon_discount; ?>">-<?php echo number_format($coupon_discount, 2, ',', ' '); ?> zł</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center pt-3 text-lg font-bold">
                                <span>Razem</span>
                                <span class="text-primary final-total"><?php echo number_format($final_total, 2, ',', ' '); ?> zł</span>
                            </div>
                        </div>
                        
                        <!-- Formularz kuponu -->
                        <form method="post" class="mb-6">
                            <div class="mb-4">
                                <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1">Kod promocyjny</label>
                                <div class="flex">
                                    <input type="text" id="coupon_code" name="coupon_code" value="<?php echo $coupon_code; ?>" 
                                           class="flex-grow border-gray-300 rounded-l-lg focus:ring-primary focus:border-primary">
                                    <button type="submit" name="apply_coupon" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-r-lg border border-gray-300 hover:bg-gray-200 transition">
                                        Zastosuj
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <a href="checkout.php" class="block w-full bg-primary text-white text-center py-3 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                            Przejdź do kasy
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal logowania -->
<div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Zaloguj się</h2>
            <button onclick="hideLoginModal()" class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <p class="text-gray-600 mb-6">Aby przejść do kasy, musisz się zalogować.</p>
        <div class="flex gap-4">
            <a href="login.php" class="flex-1 bg-primary text-white text-center py-2 rounded-button hover:bg-opacity-90 transition">
                Zaloguj się
            </a>
            <a href="register.php" class="flex-1 bg-gray-200 text-gray-800 text-center py-2 rounded-button hover:bg-gray-300 transition">
                Zarejestruj się
            </a>
        </div>
    </div>
</div>

<style>
.cart-row {
    transition: all 0.3s ease-out;
    transform-origin: left;
    overflow: hidden;
}

.cart-row.removing {
    opacity: 0;
    transform: translateX(20px);
    height: 0;
    padding: 0;
    margin: 0;
    border: none;
}

.notification {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    background-color: #ef4444;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    z-index: 50;
    transform: translateY(100%);
    opacity: 0;
    transition: all 0.3s ease-out;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
}

/* Nowe style dla tabeli */
.cart-table-container {
    overflow: hidden;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

@media (max-width: 768px) {
    .cart-table-container {
        overflow-x: auto;
    }
}
</style>

<script>
function showLoginModal() {
    document.getElementById('loginModal').classList.remove('hidden');
    document.getElementById('loginModal').classList.add('flex');
}

function hideLoginModal() {
    document.getElementById('loginModal').classList.remove('flex');
    document.getElementById('loginModal').classList.add('hidden');
}

function updatePrices() {
    let cartTotal = 0;
    let cartCount = 0;
    
    // Aktualizacja cen dla każdego produktu
    document.querySelectorAll('tr[data-product-id]').forEach(row => {
        const quantityInput = row.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value);
        const priceElement = row.querySelector('.price-value');
        const totalElement = row.querySelector('.item-total');
        const price = parseFloat(priceElement.getAttribute('data-price'));
        
        const itemTotal = price * quantity;
        totalElement.textContent = itemTotal.toFixed(2).replace('.', ',') + ' zł';
        
        cartTotal += itemTotal;
        cartCount += quantity;
    });
    
    // Aktualizacja podsumowania
    document.querySelector('.cart-total').textContent = cartTotal.toFixed(2).replace('.', ',') + ' zł';
    document.querySelector('.cart-count').textContent = cartCount;
    
    // Aktualizacja sumy końcowej (z uwzględnieniem kuponu jeśli jest)
    const couponDiscount = parseFloat(document.querySelector('.coupon-discount')?.getAttribute('data-discount') || 0);
    const finalTotal = cartTotal - couponDiscount;
    document.querySelector('.final-total').textContent = finalTotal.toFixed(2).replace('.', ',') + ' zł';
}

document.addEventListener('DOMContentLoaded', function() {
    const checkoutButton = document.querySelector('a[href="checkout.php"]');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            <?php if (!isLoggedIn()): ?>
                showLoginModal();
            <?php else: ?>
                window.location.href = 'checkout.php';
            <?php endif; ?>
        });
    }

    const incrementButtons = document.querySelectorAll('.increment-qty');
    incrementButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.getAttribute('max'));
            
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
                updatePrices();
            } else {
                alert('Nie możesz dodać więcej tego produktu (dostępna ilość: ' + maxValue + ')');
            }
        });
    });
    
    const decrementButtons = document.querySelectorAll('.decrement-qty');
    decrementButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            
            if (currentValue > 1) {
                input.value = currentValue - 1;
                updatePrices();
            }
        });
    });

    // Obsługa ręcznego wpisywania ilości
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const currentValue = parseInt(this.value);
            const maxValue = parseInt(this.getAttribute('max'));
            
            if (currentValue < 1) {
                this.value = 1;
            } else if (currentValue > maxValue) {
                this.value = maxValue;
                alert('Nie możesz dodać więcej tego produktu (dostępna ilość: ' + maxValue + ')');
            }
            
            updatePrices();
        });
    });
    
    const updateButton = document.getElementById('update-cart');
    if (updateButton) {
        updateButton.addEventListener('click', function() {
            const rows = document.querySelectorAll('tr[data-product-id]');
            let promises = [];
            
            rows.forEach(row => {
                const productId = row.getAttribute('data-product-id');
                const quantityInput = row.querySelector('.quantity-input');
                const quantity = parseInt(quantityInput.value);
                
                if (quantity > 0) {
                    promises.push(
                        fetch('cart-actions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=update&product_id=' + productId + '&quantity=' + quantity
                        })
                        .then(response => response.json())
                    );
                }
            });
            
            Promise.all(promises)
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error updating cart:', error);
                    alert('Wystąpił błąd podczas aktualizacji koszyka');
                });
        });
    }
    
    const removeButtons = document.querySelectorAll('.remove-item');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const row = this.closest('tr');
            
            // Dodaj klasę animacji
            row.classList.add('cart-row', 'removing');
            
            fetch('cart-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        row.remove();
                        
                        // Aktualizacja liczby produktów w koszyku
                        const cartCount = document.getElementById('cartCount');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                            if (data.cart_count === 0) {
                                cartCount.classList.add('hidden');
                            }
                        }
                        
                        // Aktualizacja podsumowania
                        document.querySelector('.cart-count').textContent = data.cart_count;
                        document.querySelector('.cart-total').textContent = data.cart_total + ' zł';
                        document.querySelector('.final-total').textContent = data.final_total + ' zł';
                        
                        // Jeśli koszyk jest pusty, przeładuj stronę
                        if (data.cart_count === 0) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        }
                        
                        // Pokaż powiadomienie
                        const notification = document.createElement('div');
                        notification.className = 'notification';
                        notification.innerHTML = `
                            <div class="flex items-center">
                                <i class="ri-delete-bin-fill mr-2"></i>
                                <span>Produkt został usunięty z koszyka</span>
                            </div>
                        `;
                        document.body.appendChild(notification);
                        
                        // Pokaż powiadomienie z animacją
                        requestAnimationFrame(() => {
                            notification.classList.add('show');
                        });
                        
                        // Usuń powiadomienie
                        setTimeout(() => {
                            notification.classList.remove('show');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }, 300);
                } else {
                    alert('Wystąpił błąd: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas usuwania produktu z koszyka');
            });
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>
