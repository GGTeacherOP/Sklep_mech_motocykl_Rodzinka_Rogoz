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
    
    // Pobieranie recenzji produktu
    $reviews = [];
    $avg_rating = 0;
    $review_count = 0;
    
    // Sprawdzenie, czy tabela recenzji istnieje
    $check_table_query = "SHOW TABLES LIKE 'product_reviews'";
    $table_exists = $conn->query($check_table_query)->num_rows > 0;
    
    if ($table_exists) {
        $reviews_query = "SELECT pr.*, u.first_name, u.last_name 
                        FROM product_reviews pr 
                        LEFT JOIN users u ON pr.user_id = u.id 
                        WHERE pr.product_id = ? AND pr.status = 'approved' 
                        ORDER BY pr.created_at DESC";
        
        $stmt = $conn->prepare($reviews_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $reviews_result = $stmt->get_result();
        
        if ($reviews_result && $reviews_result->num_rows > 0) {
            $total_rating = 0;
            $review_count = $reviews_result->num_rows;
            
            while ($review = $reviews_result->fetch_assoc()) {
                $reviews[] = $review;
                $total_rating += $review['rating'];
            }
            
            if ($review_count > 0) {
                $avg_rating = round($total_rating / $review_count, 1);
            }
        }
    }
    
    // Obsługa dodawania recenzji
    $review_submitted = false;
    $review_error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
        // Sprawdzenie, czy tabela recenzji istnieje
        if (!$table_exists) {
            // Wykonanie skryptu tworzącego tabelę
            $sql_path = __DIR__ . '/database/add_reviews_table.sql';
            if (file_exists($sql_path)) {
                $sql = file_get_contents($sql_path);
                $conn->multi_query($sql);
                
                // Oczekiwanie na zakończenie wszystkich zapytań
                while ($conn->more_results() && $conn->next_result()) {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                }
                
                $table_exists = true;
            }
        }
        
        // Jeśli tabela istnieje, dodajemy recenzję
        if ($table_exists) {
            $rating = (int)$_POST['rating'];
            $title = sanitize($_POST['review_title']);
            $content = sanitize($_POST['review_content']);
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            // Walidacja
            if ($rating < 1 || $rating > 5) {
                $review_error = 'Wybierz prawidłową ocenę (1-5).';
            } elseif (empty($content)) {
                $review_error = 'Treść recenzji jest wymagana.';
            } else {
                // Sprawdzenie, czy użytkownik już dodał recenzję do tego produktu
                if ($user_id) {
                    $check_review_query = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
                    $check_stmt = $conn->prepare($check_review_query);
                    $check_stmt->bind_param("ii", $product_id, $user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result && $check_result->num_rows > 0) {
                        $review_error = 'Już dodałeś recenzję do tego produktu.';
                    }
                }
                
                if (empty($review_error)) {
                    // Dodanie recenzji
                    $insert_query = "INSERT INTO product_reviews (product_id, user_id, rating, title, content, status) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $status = $user_id ? 'pending' : 'pending'; // Wszyscy muszą przejść moderację
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("iiisss", $product_id, $user_id, $rating, $title, $content, $status);
                    
                    if ($insert_stmt->execute()) {
                        $review_submitted = true;
                    } else {
                        $review_error = 'Wystąpił błąd podczas dodawania recenzji. Spróbuj ponownie później.';
                    }
                }
            }
        } else {
            $review_error = 'System recenzji jest obecnie niedostępny. Spróbuj ponownie później.';
        }
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
                        <div class="add-to-cart-form mb-6">
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
                                <button type="button" class="add-to-cart-btn flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition flex items-center justify-center">
                                    <i class="ri-shopping-cart-line mr-2"></i> Dodaj do koszyka
                                </button>
                            </div>
                        </div>
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
                                <button class="tab-button inline-block py-4 px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="reviews">
                                    Recenzje
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
                        
                        <div class="tab-content py-6 hidden" id="reviews-content">
                            <!-- Średnia ocena i statystyki -->
                            <div class="mb-8">
                                <div class="flex flex-col md:flex-row items-start md:items-center">
                                    <div class="flex items-center mr-8 mb-4 md:mb-0">
                                        <span class="text-4xl font-bold text-gray-900 mr-3"><?php echo $avg_rating; ?></span>
                                        <div class="flex flex-col">
                                            <div class="flex mb-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-5 h-5 <?php echo $i <= round($avg_rating) ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm text-gray-500"><?php echo $review_count; ?> <?php echo $review_count == 1 ? 'recenzja' : ($review_count < 5 ? 'recenzje' : 'recenzji'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lista recenzji -->
                            <?php if (!empty($reviews)): ?>
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold mb-4">Recenzje klientów</h3>
                                <div class="space-y-6">
                                    <?php foreach ($reviews as $review): ?>
                                    <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                        <div class="flex items-center mb-2">
                                            <div class="flex mr-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                                <?php endfor; ?>
                                            </div>
                                            <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($review['title'] ?? ''); ?></h4>
                                        </div>
                                        <div class="mb-2">
                                            <p class="text-sm text-gray-500">
                                                <?php 
                                                $reviewer_name = 'Gość';
                                                if (!empty($review['first_name'])) {
                                                    $reviewer_name = htmlspecialchars($review['first_name']);
                                                    if (!empty($review['last_name'])) {
                                                        $reviewer_name .= ' ' . substr(htmlspecialchars($review['last_name']), 0, 1) . '.';
                                                    }
                                                }
                                                echo $reviewer_name . ', ' . date('d.m.Y', strtotime($review['created_at']));
                                                ?>
                                            </p>
                                        </div>
                                        <div class="text-gray-700">
                                            <p><?php echo nl2br(htmlspecialchars($review['content'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Formularz dodawania recenzji -->
                            <div>
                                <?php if ($review_submitted): ?>
                                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                                    <p>Dziękujemy za dodanie recenzji! Twoja opinia została przekazana do moderacji i zostanie opublikowana po zatwierdzeniu.</p>
                                </div>
                                <?php else: ?>
                                <h3 class="text-lg font-semibold mb-4">Dodaj recenzję</h3>
                                
                                <?php if (!empty($review_error)): ?>
                                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                                    <p><?php echo $review_error; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <form action="" method="POST" class="space-y-4">
                                    <input type="hidden" name="add_review" value="1">
                                    
                                    <div>
                                        <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Ocena *</label>
                                        <div class="flex items-center">
                                            <div class="rating-stars flex" id="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="rating-star w-8 h-8 text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="<?php echo $i; ?>">
                                                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                    </svg>
                                                </button>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="rating-input" value="0">
                                            <span class="ml-2 text-sm text-gray-500" id="rating-text">Wybierz ocenę</span>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="review_title" class="block text-sm font-medium text-gray-700 mb-1">Tytuł</label>
                                        <input type="text" id="review_title" name="review_title" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="review_content" class="block text-sm font-medium text-gray-700 mb-1">Treść recenzji *</label>
                                        <textarea id="review_content" name="review_content" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                                    </div>
                                    
                                    <?php if (!isset($_SESSION['user_id'])): ?>
                                    <div class="bg-yellow-100 text-yellow-800 p-4 rounded">
                                        <p class="text-sm">Dodajesz recenzję jako gość. <a href="login.php" class="text-blue-600 hover:underline">Zaloguj się</a>, aby Twoja recenzja była powiązana z Twoim kontem.</p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <button type="submit" class="bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 transition">Dodaj recenzję</button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
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
                                    <button type="button" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition add-to-cart-related" data-product-id="<?php echo $related['id']; ?>">
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
$extra_js = "
<script>
document.addEventListener(\"DOMContentLoaded\", function() {
    console.log(\"DOM załadowany\");
    
    // Obsługa zakładek
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener(\"click\", function() {
            // Usuń aktywne klasy ze wszystkich przycisków
            tabButtons.forEach(btn => {
                btn.classList.remove(\"border-primary\", \"text-primary\");
                btn.classList.add(\"border-transparent\", \"text-gray-500\");
            });
            
            // Dodaj aktywne klasy do klikniętego przycisku
            this.classList.remove(\"border-transparent\", \"text-gray-500\");
            this.classList.add(\"border-primary\", \"text-primary\");
            
            // Ukryj wszystkie zakładki
            tabContents.forEach(content => {
                content.classList.add(\"hidden\");
            });
            
            // Pokaż wybraną zakładkę
            const tabId = this.getAttribute(\"data-tab\");
            document.getElementById(tabId + \"-content\").classList.remove(\"hidden\");
        });
    });

    // Obsługa ocen gwiazdkowych w formularzu recenzji
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-input');
    const ratingText = document.getElementById('rating-text');
    
    if (ratingStars.length > 0 && ratingInput && ratingText) {
        ratingStars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                ratingInput.value = rating;
                
                // Aktualizacja tekstu oceny
                let ratingLabel = '';
                switch(rating) {
                    case 1: ratingLabel = 'Bardzo słabo'; break;
                    case 2: ratingLabel = 'Słabo'; break;
                    case 3: ratingLabel = 'Przeciętnie'; break;
                    case 4: ratingLabel = 'Dobrze'; break;
                    case 5: ratingLabel = 'Doskonale'; break;
                }
                ratingText.textContent = ratingLabel;
                
                // Aktualizacja wyglądu gwiazdek
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
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
    
    // Funkcja do wyświetlania powiadomienia
    function showNotification(message, type = \"success\") {
        // Usuń istniejące powiadomienie jeśli istnieje
        const existingNotification = document.querySelector(\".notification\");
        if (existingNotification) {
            existingNotification.remove();
        }

        // Stwórz nowe powiadomienie
        const notification = document.createElement(\"div\");
        notification.className = `notification fixed top-20 right-4 z-40 flex items-center p-4 mb-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        
        // Dodaj odpowiedni kolor w zależności od typu
        if (type === \"success\") {
            notification.classList.add(\"bg-green-100\", \"text-green-800\", \"border\", \"border-green-200\");
        } else {
            notification.classList.add(\"bg-red-100\", \"text-red-800\", \"border\", \"border-red-200\");
        }

        // Dodaj ikonę
        const icon = document.createElement(\"i\");
        icon.className = type === \"success\" ? \"ri-checkbox-circle-fill mr-2 text-xl\" : \"ri-error-warning-fill mr-2 text-xl\";
        notification.appendChild(icon);

        // Dodaj tekst
        const text = document.createElement(\"span\");
        text.className = \"text-sm font-medium\";
        text.textContent = message;
        notification.appendChild(text);

        // Dodaj przycisk zamknięcia
        const closeButton = document.createElement(\"button\");
        closeButton.className = \"ml-4 text-gray-500 hover:text-gray-700 focus:outline-none\";
        closeButton.innerHTML = \"<i class=\\\"ri-close-line text-xl\\\"></i>\";
        closeButton.onclick = () => notification.remove();
        notification.appendChild(closeButton);

        // Dodaj do body
        document.body.appendChild(notification);

        // Animacja wejścia
        setTimeout(() => {
            notification.classList.remove(\"translate-x-full\");
        }, 100);

        // Automatyczne zamknięcie po 3 sekundach
        setTimeout(() => {
            notification.classList.add(\"translate-x-full\");
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function addToCart(productId, quantity = 1) {
        console.log(\"Dodawanie do koszyka:\", productId, quantity);
        
        fetch(\"cart-actions.php\", {
            method: \"POST\",
            headers: {
                \"Content-Type\": \"application/x-www-form-urlencoded\",
            },
            body: \"action=add&product_id=\" + productId + \"&quantity=\" + quantity
        })
        .then(response => response.json())
        .then(data => {
            console.log(\"Odpowiedź:\", data);
            if (data.success) {
                // Aktualizacja liczby produktów w koszyku
                const cartCount = document.getElementById(\"cartCount\");
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                    cartCount.classList.remove(\"hidden\");
                }
                
                showNotification(\"Produkt został dodany do koszyka\", \"success\");
            } else {
                showNotification(\"Wystąpił błąd: \" + data.message, \"error\");
            }
        })
        .catch(error => {
            console.error(\"Error:\", error);
            showNotification(\"Wystąpił błąd podczas dodawania produktu do koszyka\", \"error\");
        });
    }

    // Obsługa przycisków +/- ilości
    const incrementBtn = document.querySelector(\".increment-qty\");
    const decrementBtn = document.querySelector(\".decrement-qty\");
    const quantityInput = document.getElementById(\"quantity\");
    
    console.log(\"Przyciski ilości:\", { incrementBtn, decrementBtn, quantityInput });
    
    if (incrementBtn) {
        incrementBtn.addEventListener(\"click\", function() {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.getAttribute(\"max\"));
            
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
    
    if (decrementBtn) {
        decrementBtn.addEventListener(\"click\", function() {
            const currentValue = parseInt(quantityInput.value);
            
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }

    // Obsługa głównego przycisku dodawania do koszyka
    const addToCartBtn = document.querySelector(\".add-to-cart-btn\");
    console.log(\"Główny przycisk:\", addToCartBtn);
    
    if (addToCartBtn) {
        addToCartBtn.addEventListener(\"click\", function() {
            const productId = document.querySelector(\"input[name=\\\"product_id\\\"]\").value;
            const quantity = document.getElementById(\"quantity\").value;
            console.log(\"Kliknięto główny przycisk:\", productId, quantity);
            addToCart(productId, quantity);
        });
    }

    // Obsługa przycisków dodawania do koszyka dla produktów powiązanych
    const relatedButtons = document.querySelectorAll(\".add-to-cart-related\");
    console.log(\"Przyciski powiązane:\", relatedButtons);
    
    relatedButtons.forEach(button => {
        button.addEventListener(\"click\", function() {
            const productId = this.getAttribute(\"data-product-id\");
            console.log(\"Kliknięto przycisk powiązany:\", productId);
            addToCart(productId, 1);
        });
    });
});
</script>";

include 'includes/footer.php';
?>
