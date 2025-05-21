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
                    WHERE p.slug = '$product_slug' AND p.status = 'published'";
    $product_result = $conn->query($product_query);

    if (!$product_result || $product_result->num_rows === 0) {
        header("Location: ProductCatalog.php");
        exit;
    }

    $product = $product_result->fetch_assoc();
    $product_id = $product['id'];
    $page_title = $product['name'] . " | MotoShop";

    // Pobieranie zdjęć produktu
    $images_query = "SELECT * FROM product_images WHERE product_id = $product_id ORDER BY is_main DESC, id ASC";
    $images_result = $conn->query($images_query);

    $product_images = [];
    if ($images_result && $images_result->num_rows > 0) {
        while ($image = $images_result->fetch_assoc()) {
            $product_images[] = $image;
        }
    }

    // Jeśli nie ma zdjęć, dodajemy placeholder
    if (empty($product_images)) {
        $product_images[] = [
            'image_path' => 'assets/images/placeholder.jpg',
            'alt_text' => $product['name'],
            'is_main' => 1
        ];
    }

    // Pobieranie produktów powiązanych
    $related_products = [];
    $related_query = "SELECT p.*, pi.image_path 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                WHERE p.category_id = " . $product['category_id'] . " 
                AND p.id != $product_id 
                AND p.status = 'published'
                LIMIT 4";
    $related_result = $conn->query($related_query);

    if ($related_result && $related_result->num_rows > 0) {
        while ($related = $related_result->fetch_assoc()) {
            $related_products[] = $related;
        }
    }

    // Obsługa recenzji
    $reviews = [];
    $total_reviews = 0;
    $avg_rating = 0;

    // Sprawdzenie czy tabela reviews istnieje
    $tables_query = "SHOW TABLES LIKE 'reviews'";
    $tables_result = $conn->query($tables_query);

    if ($tables_result && $tables_result->num_rows > 0) {
        // Tabela recenzji istnieje, pobieramy dane
        $reviews_query = "SELECT r.*, u.username, u.first_name, u.last_name 
                        FROM reviews r 
                        LEFT JOIN users u ON r.user_id = u.id 
                        WHERE r.product_id = $product_id AND r.status = 'approved' 
                        ORDER BY r.created_at DESC";
        $reviews_result = $conn->query($reviews_query);

        if ($reviews_result && $reviews_result->num_rows > 0) {
            $total_reviews = $reviews_result->num_rows;
            $sum_rating = 0;
            
            while ($review = $reviews_result->fetch_assoc()) {
                $reviews[] = $review;
                $sum_rating += $review['rating'];
            }
            
            $avg_rating = $total_reviews > 0 ? round($sum_rating / $total_reviews, 1) : 0;
        }
    }
} catch (Exception $e) {
    // Logowanie błędu
    error_log("Błąd na stronie produktu: " . $e->getMessage());
    
    // Przekierowanie na stronę katalogu w przypadku błędu
    header("Location: ProductCatalog.php?error=product_error");
    exit;
}

// Obliczenie rabatu, jeśli jest
$discount_percentage = 0;
if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
    $discount_percentage = round(100 - ($product['sale_price'] / $product['price'] * 100));
}

// Obsługa dodawania recenzji
$review_message = '';
$user_review = null;

if (isLoggedIn() && isset($_POST['submit_review'])) {
    $user_id = $_SESSION['user_id'];
    
    // Sprawdzenie czy użytkownik już dodał recenzję
    $check_review_query = "SELECT * FROM reviews WHERE product_id = $product_id AND user_id = $user_id";
    $check_review_result = $conn->query($check_review_query);
    
    if ($check_review_result && $check_review_result->num_rows > 0) {
        // Aktualizacja istniejącej recenzji
        $user_review = $check_review_result->fetch_assoc();
        $review_id = $user_review['id'];
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $comment = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';
        
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }
        
        $update_review = "UPDATE reviews SET rating = $rating, comment = '$comment', updated_at = NOW() WHERE id = $review_id";
        
        if ($conn->query($update_review) === TRUE) {
            $review_message = 'Twoja opinia została zaktualizowana.';
            
            // Odświeżenie strony, aby pokazać zaktualizowaną recenzję
            header("Location: product.php?slug=$product_slug&review_updated=1");
            exit;
        } else {
            $review_message = 'Wystąpił błąd podczas aktualizacji opinii.';
        }
    } else {
        // Dodanie nowej recenzji
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $comment = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';
        
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }
        
        $insert_review = "INSERT INTO reviews (product_id, user_id, rating, comment, status, created_at) 
                        VALUES ($product_id, $user_id, $rating, '$comment', 'pending', NOW())";
        
        if ($conn->query($insert_review) === TRUE) {
            $review_message = 'Dziękujemy za dodanie opinii. Zostanie ona opublikowana po zatwierdzeniu przez administratora.';
        } else {
            $review_message = 'Wystąpił błąd podczas dodawania opinii.';
        }
    }
}

// Sprawdzenie czy użytkownik ma już recenzję
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $check_review_query = "SELECT * FROM reviews WHERE product_id = $product_id AND user_id = $user_id";
    $check_review_result = $conn->query($check_review_query);
    
    if ($check_review_result && $check_review_result->num_rows > 0) {
        $user_review = $check_review_result->fetch_assoc();
    }
}

// Sprawdzenie parametru review_updated
if (isset($_GET['review_updated']) && $_GET['review_updated'] == 1) {
    $review_message = 'Twoja opinia została zaktualizowana.';
}

include 'includes/header.php';
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <nav class="flex">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-700 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <a href="ProductCatalog.php" class="text-gray-700 hover:text-primary">
                                    Katalog produktów
                                </a>
                            </div>
                        </li>
                        <?php if (!empty($product['category_name'])): ?>
                        <li>
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <a href="ProductCatalog.php?category=<?php echo $product['category_id']; ?>" class="text-gray-700 hover:text-primary">
                                    <?php echo $product['category_name']; ?>
                                </a>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <span class="text-primary font-medium"><?php echo $product['name']; ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if (!empty($review_message)): ?>
            <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-8">
                <?php echo $review_message; ?>
            </div>
            <?php endif; ?>
            
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
                                <img id="mainImage" src="/<?php echo ltrim($product_images[0]['image_path'], '/'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-80 object-contain rounded-lg">
                            </div>
                            <?php if (count($product_images) > 1): ?>
                            <div class="product-thumbnails flex space-x-2 overflow-x-auto py-2">
                                <?php foreach ($product_images as $index => $image): ?>
                                <div class="flex-shrink-0">
                                    <img src="/<?php echo ltrim($image['image_path'], '/'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-20 h-20 object-cover rounded cursor-pointer thumbnail-image <?php echo $index === 0 ? 'border-2 border-primary' : 'border-2 border-transparent'; ?>" 
                                         data-index="<?php echo $index; ?>" 
                                         data-src="/<?php echo ltrim($image['image_path'], '/'); ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Informacje o produkcie -->
                    <div class="p-6 border-t md:border-t-0 md:border-l border-gray-200">
                        <h1 class="text-2xl font-bold mb-2"><?php echo $product['name']; ?></h1>
                        
                        <div class="flex items-center mb-4">
                            <?php if ($total_reviews > 0): ?>
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="ri-star-<?php echo $i <= round($avg_rating) ? 'fill' : 'line'; ?> text-yellow-400"></i>
                                <?php endfor; ?>
                                <span class="ml-2 text-sm text-gray-600"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? 'opinia' : 'opinii'; ?>)</span>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center text-gray-400">
                                <i class="ri-star-line"></i>
                                <i class="ri-star-line"></i>
                                <i class="ri-star-line"></i>
                                <i class="ri-star-line"></i>
                                <i class="ri-star-line"></i>
                                <span class="ml-2 text-sm text-gray-600">Brak opinii</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <?php if (!empty($product['sale_price'])): ?>
                            <span class="text-primary text-2xl font-bold"><?php echo number_format($product['sale_price'], 2, ',', ' '); ?> zł</span>
                            <span class="text-gray-500 line-through ml-3"><?php echo number_format($product['price'], 2, ',', ' '); ?> zł</span>
                            <?php else: ?>
                            <span class="text-primary text-2xl font-bold"><?php echo number_format($product['price'], 2, ',', ' '); ?> zł</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($product['brand_name'])): ?>
                        <div class="mb-4">
                            <span class="text-gray-600">Marka:</span>
                            <span class="font-medium ml-2"><?php echo $product['brand_name']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['sku'])): ?>
                        <div class="mb-4">
                            <span class="text-gray-600">SKU:</span>
                            <span class="font-medium ml-2"><?php echo $product['sku']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <span class="text-gray-600">Dostępność:</span>
                            <?php if ($product['stock'] > 0): ?>
                            <span class="text-green-600 font-medium ml-2">W magazynie (<?php echo $product['stock']; ?>)</span>
                            <?php else: ?>
                            <span class="text-red-600 font-medium ml-2">Brak w magazynie</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($product['short_description'])): ?>
                        <div class="mb-6">
                            <p class="text-gray-700"><?php echo nl2br($product['short_description']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['stock'] > 0): ?>
                        <form action="cart-actions.php" method="post" class="add-to-cart-form mb-6">
                            <input type="hidden" name="action" value="add">
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
                                <button type="submit" class="add-to-cart-btn flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                    <i class="ri-shopping-cart-line mr-2"></i> Dodaj do koszyka
                                </button>
                                <button type="button" class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition add-to-wishlist">
                                    <i class="ri-heart-line"></i>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <div class="pt-6 border-t border-gray-200">
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center text-gray-700">
                                    <i class="ri-truck-line text-xl mr-2"></i>
                                    <span>Darmowa dostawa od 300 zł</span>
                                </div>
                                <div class="flex items-center text-gray-700">
                                    <i class="ri-shield-check-line text-xl mr-2"></i>
                                    <span>Gwarancja 24 miesiące</span>
                                </div>
                            </div>
                        </div>
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
                                <button class="tab-button inline-block py-4 px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="specs">
                                    Specyfikacja
                                </button>
                                <button class="tab-button inline-block py-4 px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="reviews">
                                    Opinie (<?php echo $total_reviews; ?>)
                                </button>
                            </div>
                        </div>
                        
                        <div class="tab-content py-6" id="description-content">
                            <?php if (!empty($product['description'])): ?>
                            <div class="prose max-w-none">
                                <?php echo nl2br($product['description']); ?>
                            </div>
                            <?php else: ?>
                            <p class="text-gray-500 italic">Brak opisu produktu.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content py-6 hidden" id="specs-content">
                            <?php if (!empty($product['specifications'])): ?>
                            <div class="prose max-w-none">
                                <?php echo nl2br($product['specifications']); ?>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Marka</span>
                                        <span class="font-medium"><?php echo $product['brand_name'] ?? 'Nieznana'; ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($product['sku'])): ?>
                                <div class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Kod produktu</span>
                                        <span class="font-medium"><?php echo $product['sku']; ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($product['weight'])): ?>
                                <div class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Waga</span>
                                        <span class="font-medium"><?php echo $product['weight']; ?> kg</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($product['dimensions'])): ?>
                                <div class="border-b pb-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Wymiary</span>
                                        <span class="font-medium"><?php echo $product['dimensions']; ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content py-6 hidden" id="reviews-content">
                            <?php if (!empty($reviews)): ?>
                                <div class="space-y-6 mb-8">
                                    <?php foreach ($reviews as $review): ?>
                                    <div class="border-b border-gray-200 pb-6">
                                        <div class="flex items-center mb-2">
                                            <div class="flex items-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="ri-star-<?php echo $i <= $review['rating'] ? 'fill' : 'line'; ?> text-yellow-400"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="ml-2 text-sm font-medium">
                                                <?php echo !empty($review['first_name']) ? $review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.' : $review['username']; ?>
                                            </span>
                                            <span class="ml-auto text-xs text-gray-500">
                                                <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="text-gray-700">
                                            <?php echo nl2br($review['comment']); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 mb-6">Ten produkt nie ma jeszcze żadnych opinii. Bądź pierwszy i podziel się swoją opinią!</p>
                            <?php endif; ?>
                            
                            <?php if (isLoggedIn()): ?>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4"><?php echo $user_review ? 'Edytuj swoją opinię' : 'Dodaj opinię'; ?></h3>
                                
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ocena</label>
                                        <div class="flex space-x-2 rating-select">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="rating-star cursor-pointer text-2xl <?php echo ($user_review && $i <= $user_review['rating']) || (!$user_review && $i <= 5) ? 'text-yellow-400' : 'text-gray-300'; ?>" data-value="<?php echo $i; ?>">
                                                <i class="ri-star-fill"></i>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="rating-input" value="<?php echo $user_review ? $user_review['rating'] : 5; ?>">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Komentarz</label>
                                        <textarea id="comment" name="comment" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo $user_review ? $user_review['comment'] : ''; ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="submit_review" class="bg-primary text-white py-2 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                                        <?php echo $user_review ? 'Aktualizuj opinię' : 'Dodaj opinię'; ?>
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <div class="bg-gray-50 p-6 rounded-lg text-center">
                                <p class="mb-4">Zaloguj się, aby dodać opinię o tym produkcie.</p>
                                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="inline-block bg-primary text-white py-2 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                                    Zaloguj się
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Powiązane produkty -->
            <?php if (!empty($related_products)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold mb-6">Produkty powiązane</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($related_products as $related): ?>
                    <?php
                    $related_image = $related['image_path'] ?? 'assets/images/placeholder.jpg';
                    
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
                            
                            <img src="<?php echo $related_image; ?>" alt="<?php echo $related['name']; ?>" class="w-full h-48 object-cover">
                        </a>
                        
                        <div class="p-4">
                            <a href="product.php?slug=<?php echo $related['slug']; ?>" class="block mb-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition line-clamp-2"><?php echo $related['name']; ?></h3>
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
                                <button onclick="addToCart(<?php echo $related['id']; ?>)" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition">
                                    <i class="ri-shopping-cart-line"></i>
                                </button>
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
// Obsługa przełączania między zakładkami
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Przełączanie klas dla przycisków zakładek
            tabButtons.forEach(btn => {
                btn.classList.remove('border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-primary', 'text-primary');
            
            // Przełączanie widoczności zawartości zakładek
            const tabId = this.dataset.tab;
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tabId + '-content').classList.remove('hidden');
        });
    });
    
    // Obsługa miniatur zdjęć
    const thumbnails = document.querySelectorAll('.thumbnail-image');
    const mainImage = document.getElementById('mainImage');
    
    if (thumbnails.length > 0 && mainImage) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Zmiana głównego zdjęcia
                mainImage.src = this.dataset.src;
                
                // Aktualizacja stylu aktywnej miniatury
                thumbnails.forEach(t => {
                    t.classList.remove('border-primary');
                    t.classList.add('border-transparent');
                });
                this.classList.remove('border-transparent');
                this.classList.add('border-primary');
                mainImage.alt = this.alt;
                
                // Aktualizacja aktywnej miniatury
                thumbnails.forEach(t => t.classList.remove('border-primary'));
                thumbnails.forEach(t => t.classList.add('border-transparent'));
                this.classList.remove('border-transparent');
                this.classList.add('border-primary');
            });
        });
    }
    
    // Obsługa przycisku "+" przy ilości
    const incrementBtn = document.querySelector('.increment-qty');
    if (incrementBtn) {
        incrementBtn.addEventListener('click', function() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.getAttribute('max'));
            
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
            } else {
                alert('Nie możesz dodać więcej tego produktu (dostępna ilość: ' + maxValue + ')');
            }
        });
    }
    
    // Obsługa przycisku "-" przy ilości
    const decrementBtn = document.querySelector('.decrement-qty');
    if (decrementBtn) {
        decrementBtn.addEventListener('click', function() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    }
    
    // Obsługa dodawania do koszyka
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const form = document.querySelector('.add-to-cart-form');
            const productId = form.querySelector('input[name="product_id"]').value;
            const quantity = form.querySelector('input[name="quantity"]').value;
            
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
        });
    }
    
    // Obsługa gwiazdek do oceny
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-input');
    
    if (ratingStars.length > 0 && ratingInput) {
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                ratingInput.value = value;
                
                // Aktualizacja wyglądu gwiazdek
                ratingStars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('text-yellow-400');
                        s.classList.remove('text-gray-300');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });
            });
        });
    }
});

// Funkcja do dodawania produktów powiązanych do koszyka
function addToCart(productId) {
    fetch('cart-actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&product_id=' + productId + '&quantity=1'
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
</script>
EOT;

include 'includes/footer.php';
?>
