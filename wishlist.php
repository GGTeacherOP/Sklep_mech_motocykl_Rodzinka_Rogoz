<?php
// Strona listy życzeń
$page_title = "Moja lista życzeń | MotoShop";
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$wishlist_items = [];

// Pobieranie listy życzeń użytkownika
$wishlist_stmt = $conn->prepare("
    SELECT w.id 
    FROM wishlists w 
    WHERE w.user_id = ?
");
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

if ($wishlist_result->num_rows > 0) {
    $wishlist = $wishlist_result->fetch_assoc();
    $wishlist_id = $wishlist['id'];
    
    // Pobieranie produktów z listy życzeń
    $items_stmt = $conn->prepare("
        SELECT p.*, pi.image_path 
        FROM wishlist_items wi
        JOIN products p ON wi.product_id = p.id
        LEFT JOIN (
            SELECT product_id, image_path 
            FROM product_images 
            WHERE is_main = 1
        ) pi ON p.id = pi.product_id
        WHERE wi.wishlist_id = ?
        ORDER BY wi.created_at DESC
    ");
    $items_stmt->bind_param("i", $wishlist_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $wishlist_items[] = $item;
    }
    $items_stmt->close();
}
$wishlist_stmt->close();

include 'includes/header.php';
?>

<!-- Główna zawartość strony -->
<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-3">Moja lista życzeń</h1>
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
                                <span class="text-primary font-medium">Moja lista życzeń</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if (empty($wishlist_items)): ?>
            <!-- Pusta lista życzeń -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="text-gray-500 mb-4"><i class="ri-heart-line text-5xl"></i></div>
                <h2 class="text-xl font-semibold mb-2">Twoja lista życzeń jest pusta</h2>
                <p class="text-gray-500 mb-6">Dodaj produkty do listy życzeń podczas przeglądania sklepu.</p>
                <a href="ProductCatalog.php" class="inline-block bg-primary text-white py-2 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                    Przeglądaj produkty
                </a>
            </div>
            <?php else: ?>
            <!-- Lista produktów -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Zapisane produkty</h2>
                    <span class="text-gray-500"><?php echo count($wishlist_items); ?> <?php echo count($wishlist_items) === 1 ? 'produkt' : (count($wishlist_items) < 5 ? 'produkty' : 'produktów'); ?></span>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 text-green-700 p-4 mb-6 rounded-lg">
                    <?php echo $_SESSION['message']; ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Produkt
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cena
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <span class="sr-only">Akcje</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($wishlist_items as $item): ?>
                            <tr class="hover:bg-gray-50" data-product-id="<?php echo $item['id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-16 h-16">
                                            <?php if ($item['image_path']): ?>
                                            <img class="w-16 h-16 object-cover rounded" src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="ri-image-line text-gray-400 text-xl"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <a href="product.php?slug=<?php echo $item['slug']; ?>" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                            <?php if (!empty($item['sku'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">SKU: <?php echo $item['sku']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($item['sale_price']): ?>
                                    <div class="text-sm font-medium text-primary"><?php echo number_format($item['sale_price'], 2, ',', ' '); ?> zł</div>
                                    <div class="text-xs text-gray-500 line-through"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                    <?php else: ?>
                                    <div class="text-sm font-medium text-primary"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($item['stock'] > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Dostępny
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Niedostępny
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2 justify-end">
                                        <?php if ($item['stock'] > 0): ?>
                                        <button type="button" class="move-to-cart-btn text-primary hover:text-primary-dark" title="Dodaj do koszyka">
                                            <i class="ri-shopping-cart-line"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="remove-from-wishlist-btn text-red-600 hover:text-red-800" title="Usuń z listy życzeń">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-8 flex justify-between">
                    <a href="ProductCatalog.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                        <i class="ri-arrow-left-line mr-2"></i> Wróć do zakupów
                    </a>
                    <?php if (!empty($wishlist_items)): ?>
                    <button id="clear-wishlist" class="inline-flex items-center text-red-600 hover:text-red-800">
                        <i class="ri-delete-bin-line mr-2"></i> Wyczyść listę życzeń
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<!-- JavaScript dla listy życzeń -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa usuwania produktu z listy życzeń
    document.querySelectorAll('.remove-from-wishlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;
            
            removeFromWishlist(productId, row);
        });
    });
    
    // Obsługa dodawania do koszyka
    document.querySelectorAll('.move-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;
            
            moveToCart(productId, row);
        });
    });
    
    // Obsługa czyszczenia listy życzeń
    const clearWishlistBtn = document.getElementById('clear-wishlist');
    if (clearWishlistBtn) {
        clearWishlistBtn.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz usunąć wszystkie produkty z listy życzeń?')) {
                // Tu kod do czyszczenia listy - można zaimplementować w przyszłości
                document.querySelectorAll('tr[data-product-id]').forEach(row => {
                    const productId = row.dataset.productId;
                    removeFromWishlist(productId, row, false);
                });
            }
        });
    }
    
    // Funkcja do usuwania produktu z listy życzeń
    function removeFromWishlist(productId, row, showMessage = true) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);
        
        fetch('wishlist-actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    
                    // Aktualizacja licznika produktów
                    updateProductCount(data.wishlist_count);
                    
                    // Sprawdzenie czy lista jest pusta
                    if (document.querySelectorAll('tr[data-product-id]').length === 0) {
                        location.reload(); // Przeładowanie strony, aby pokazać pustą listę
                    }
                    
                    if (showMessage) {
                        showNotification(data.message);
                    }
                }, 300);
            } else {
                showNotification('Wystąpił błąd. Spróbuj ponownie.', 'error');
            }
        })
        .catch(() => {
            showNotification('Wystąpił błąd. Spróbuj ponownie.', 'error');
        });
    }
    
    // Funkcja do przenoszenia produktu do koszyka
    function moveToCart(productId, row) {
        const formData = new FormData();
        formData.append('action', 'move_to_cart');
        formData.append('product_id', productId);
        
        fetch('wishlist-actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    
                    // Aktualizacja licznika produktów
                    updateProductCount(data.wishlist_count);
                    
                    // Aktualizacja koszyka w nagłówku - można zaimplementować
                    
                    // Sprawdzenie czy lista jest pusta
                    if (document.querySelectorAll('tr[data-product-id]').length === 0) {
                        location.reload(); // Przeładowanie strony, aby pokazać pustą listę
                    }
                    
                    showNotification(data.message);
                }, 300);
            } else {
                showNotification('Wystąpił błąd. Spróbuj ponownie.', 'error');
            }
        })
        .catch(() => {
            showNotification('Wystąpił błąd. Spróbuj ponownie.', 'error');
        });
    }
    
    // Funkcja do aktualizacji licznika produktów
    function updateProductCount(count) {
        const counterEl = document.querySelector('.flex.justify-between.items-center.mb-6 span');
        if (counterEl) {
            let suffix = 'produktów';
            if (count === 1) suffix = 'produkt';
            else if (count > 1 && count < 5) suffix = 'produkty';
            
            counterEl.textContent = `${count} ${suffix}`;
        }
    }
    
    // Funkcja do wyświetlania powiadomień
    function showNotification(message, type = 'success') {
        // Można zaimplementować wyświetlanie eleganckich powiadomień
        alert(message);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
