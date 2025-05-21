<?php
// Włączenie wyświetlania błędów na stronie dla celów diagnostycznych
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "Produkt | MotoShop";
require_once 'includes/config.php';

// Sprawdzenie czy slug produktu został przekazany
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header("Location: ProductCatalog.php");
    exit;
}

$product_slug = sanitize($_GET['slug']);

try {
    // Pobieranie danych produktu
    $product_query = "SELECT p.*, c.name as category_name, b.name as brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.slug = ? AND p.status = 'published'";
    
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("s", $product_slug);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if (!$product_result || $product_result->num_rows === 0) {
        // Jeśli produkt nie istnieje, przekieruj na katalog
        header("Location: ProductCatalog.php");
        exit;
    }

    $product = $product_result->fetch_assoc();
    $product_id = $product['id'];
    $page_title = $product['name'] . " | MotoShop";

    // Pobieranie zdjęć produktu
    $product_images = [];
    $images_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $conn->prepare($images_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $images_result = $stmt->get_result();

    if ($images_result && $images_result->num_rows > 0) {
        while ($image = $images_result->fetch_assoc()) {
            $product_images[] = $image;
        }
    }

    // Jeśli nie ma zdjęć, dodajemy placeholder
    if (empty($product_images)) {
        $product_images[] = [
            'image_path' => 'assets/images/placeholder.jpg',
            'is_main' => 1
        ];
    }

    // Pobieranie produktów powiązanych z tej samej kategorii
    $related_products = [];
    if (isset($product['category_id']) && !empty($product['category_id'])) {
        $related_query = "SELECT p.*, pi.image_path 
                    FROM products p 
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                    WHERE p.category_id = ? 
                    AND p.id != ? 
                    AND p.status = 'published'
                    LIMIT 4";
        
        $stmt = $conn->prepare($related_query);
        $stmt->bind_param("ii", $product['category_id'], $product_id);
        $stmt->execute();
        $related_result = $stmt->get_result();

        if ($related_result && $related_result->num_rows > 0) {
            while ($related = $related_result->fetch_assoc()) {
                $related_products[] = $related;
            }
        }
    }

    // Obliczenie rabatu, jeśli jest
    $discount_percentage = '';
    $final_price = $product['price'];
    
    if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
        $final_price = $product['sale_price'];
        $discount_percentage = round(100 - ($product['sale_price'] / $product['price'] * 100));
    }

    // Sprawdzenie dostępności produktu
    $stock_status = 'Dostępny';
    if ($product['stock'] <= 0) {
        $stock_status = 'Niedostępny';
    } elseif ($product['stock'] <= 5) {
        $stock_status = 'Ostatnie sztuki';
    }

    include 'includes/header.php';
} catch (Exception $e) {
    // Logowanie błędu
    error_log("Błąd na stronie produktu: " . $e->getMessage());
    
    // Przekierowanie w przypadku błędu
    header("Location: ProductCatalog.php?error=product_error");
    exit;
}
?>

<!-- Główna treść strony -->
<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <!-- Breadcrumbs -->
            <div class="mb-8">
                <nav class="flex">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-600 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <a href="ProductCatalog.php" class="ml-1 text-gray-600 hover:text-primary md:ml-2">
                                    Katalog
                                </a>
                            </div>
                        </li>
                        <?php if (!empty($product['category_name'])): ?>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <a href="ProductCatalog.php?category=<?php echo urlencode($product['category_id']); ?>" class="ml-1 text-gray-600 hover:text-primary md:ml-2">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </a>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ml-1 text-gray-700 md:ml-2 font-medium">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Karta produktu -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2">
                    <!-- Zdjęcia produktu -->
                    <div class="p-6">
                        <?php if (!empty($discount_percentage)): ?>
                        <div class="absolute top-6 left-6 bg-primary text-white text-sm font-semibold px-3 py-1 rounded">
                            Promocja -<?php echo $discount_percentage; ?>%
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product_images)): ?>
                        <div class="mb-4">
                            <div class="product-main-image mb-3">
                                <img id="mainImage" src="<?php echo $product_images[0]['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-80 object-contain rounded-lg">
                            </div>
                            <?php if (count($product_images) > 1): ?>
                            <div class="product-thumbnails flex space-x-2 overflow-x-auto py-2">
                                <?php foreach ($product_images as $index => $image): ?>
                                <div class="flex-shrink-0">
                                    <img src="<?php echo $image['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-20 h-20 object-cover rounded cursor-pointer thumbnail-image <?php echo $index === 0 ? 'border-2 border-primary' : 'border-2 border-transparent'; ?>" 
                                         data-index="<?php echo $index; ?>" 
                                         data-src="<?php echo $image['image_path']; ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Informacje o produkcie -->
                    <div class="p-6 border-t md:border-t-0 md:border-l border-gray-200">
                        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <?php if (!empty($product['brand_name'])): ?>
                        <div class="text-sm text-gray-500 mb-4">
                            <span>Marka: <?php echo htmlspecialchars($product['brand_name']); ?></span>
                            <?php if (!empty($product['sku'])): ?>
                            <span class="mx-2">|</span>
                            <span>SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['short_description'])): ?>
                        <div class="mb-6">
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-6">
                            <div class="flex items-center">
                                <div class="text-3xl font-bold text-primary">
                                    <?php echo number_format($final_price, 2, ',', ' '); ?> zł
                                </div>
                                <?php if (!empty($discount_percentage)): ?>
                                <div class="ml-3 text-lg text-gray-500 line-through">
                                    <?php echo number_format($product['price'], 2, ',', ' '); ?> zł
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <div class="mr-4 text-sm font-medium">
                                Status: 
                                <?php if ($product['stock'] > 0): ?>
                                <span class="text-green-600"><?php echo $stock_status; ?></span>
                                <?php else: ?>
                                <span class="text-red-600"><?php echo $stock_status; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($product['stock'] > 0): ?>
                        <form class="add-to-cart-form mb-6">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="flex items-center mb-4">
                                <label for="quantity" class="mr-3 font-medium">Ilość:</label>
                                <div class="flex items-center border rounded">
                                    <button type="button" class="px-3 py-1 text-gray-600 hover:text-primary decrement-qty">
                                        <i class="ri-subtract-line"></i>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" 
                                        class="w-12 text-center border-0 focus:ring-0 quantity-input">
                                    <button type="button" class="px-3 py-1 text-gray-600 hover:text-primary increment-qty">
                                        <i class="ri-add-line"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="addToCart(<?php echo $product_id; ?>)" class="add-to-cart-btn flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                    <i class="ri-shopping-cart-line mr-2"></i> Dodaj do koszyka
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="mb-6 px-4 py-3 bg-gray-100 rounded text-gray-700">
                            <p>Ten produkt jest obecnie niedostępny.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Opisy produktu -->
                <div class="p-6 border-t border-gray-200">
                    <div class="mb-8">
                        <div class="border-b border-gray-200">
                            <div class="flex flex-wrap -mb-px">
                                <button class="tab-button inline-block py-4 px-6 border-b-2 border-primary text-primary font-medium" data-tab="description">
                                    Opis produktu
                                </button>
                                <button class="tab-button inline-block py-4 px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="specifications">
                                    Specyfikacja
                                </button>
                            </div>
                        </div>
                        
                        <div class="tab-content py-6" id="description-content">
                            <?php if (!empty($product['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            <?php else: ?>
                                <p class="text-gray-500">Ten produkt nie posiada jeszcze opisu.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content py-6 hidden" id="specifications-content">
                            <?php if (!empty($product['specifications'])): ?>
                                <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                            <?php else: ?>
                                <p class="text-gray-500">Ten produkt nie posiada jeszcze specyfikacji.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Powiązane produkty -->
            <?php if (!empty($related_products)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold mb-6">Podobne produkty</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($related_products as $related): ?>
                    <?php 
                    // Oblicz procent rabatu jeśli jest
                    $related_discount = '';
                    if (!empty($related['sale_price']) && $related['sale_price'] < $related['price']) {
                        $related_discount = round(100 - ($related['sale_price'] / $related['price'] * 100));
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <a href="product.php?slug=<?php echo $related['slug']; ?>" class="block relative">
                            <!-- Znacznik promocji -->
                            <?php if (!empty($related_discount)): ?>
                            <div class="absolute top-3 left-3 bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                Promocja -<?php echo $related_discount; ?>%
                            </div>
                            <?php endif; ?>
                            
                            <img src="<?php echo !empty($related['image_path']) ? '/' . ltrim($related['image_path'], '/') : 'assets/images/placeholder.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                class="w-full h-48 object-cover">
                        </a>
                        
                        <div class="p-4">
                            <div class="text-xs text-gray-500 mb-1"><?php echo htmlspecialchars($related['brand_name'] ?? ''); ?></div>
                            <a href="product.php?slug=<?php echo $related['slug']; ?>" class="block mb-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition line-clamp-2"><?php echo htmlspecialchars($related['name']); ?></h3>
                            </a>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <?php if (!empty($related['sale_price'])): ?>
                                    <span class="text-primary font-bold"><?php echo number_format($related['sale_price'], 2, ',', ' '); ?> zł</span>
                                    <span class="text-gray-400 line-through text-sm ml-2"><?php echo number_format($related['price'], 2, ',', ' '); ?> zł</span>
                                    <?php else: ?>
                                    <span class="text-primary font-bold"><?php echo number_format($related['price'], 2, ',', ' '); ?> zł</span>
                                    <?php endif; ?>
                                </div>
                                <form action="cart-actions.php" method="post" class="inline">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $related['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button onclick="addToCart(<?php echo $related['id']; ?>)" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition">
                                        <i class="ri-shopping-cart-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Skrypt JS do obsługi strony produktu
$extra_js = <<<EOT
<script>
function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    fetch('cart-actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&product_id=' + productId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualizacja liczby produktów w koszyku
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove('hidden');
            }
            
            alert('Produkt został dodany do koszyka');
        } else {
            alert('Wystąpił błąd: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas dodawania produktu do koszyka');
    });
}

// Obsługa przycisków +/- ilości
document.addEventListener('DOMContentLoaded', function() {
    const incrementBtn = document.querySelector('.increment-qty');
    const decrementBtn = document.querySelector('.decrement-qty');
    const quantityInput = document.getElementById('quantity');
    
    if (incrementBtn) {
        incrementBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.getAttribute('max'));
            
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
    
    if (decrementBtn) {
        decrementBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }
});
</script>
EOT;

include 'includes/footer.php';
?>
