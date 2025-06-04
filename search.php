<?php
$page_title = "Wyniki wyszukiwania";
require_once 'includes/config.php';

// Pobierz zapytanie wyszukiwania
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Przygotuj zapytanie SQL z zabezpieczeniem przed SQL injection
$search_terms = explode(' ', $search_query);
$search_conditions = [];
$params = [];
$types = '';

foreach ($search_terms as $term) {
    $search_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ? OR c.name LIKE ?)";
    $term = "%$term%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'ssss';
}

$where_clause = !empty($search_conditions) ? 'WHERE ' . implode(' AND ', $search_conditions) : '';

// Zapytanie SQL z JOINami dla pobrania wszystkich potrzebnych informacji
$sql = "SELECT p.*, pi.image_path, b.name as brand_name, c.name as category_name 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause
        ORDER BY p.name ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

include 'includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Wyniki wyszukiwania</h1>
            <?php if (!empty($search_query)): ?>
                <p class="text-gray-600">Wyniki dla: "<?php echo htmlspecialchars($search_query); ?>"</p>
            <?php endif; ?>
        </div>

        <?php if (empty($search_query)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500">Wprowadź frazę wyszukiwania.</p>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500">Nie znaleziono produktów pasujących do wyszukiwanej frazy.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <a href="product.php?slug=<?php echo $product['slug']; ?>" class="block relative">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <?php 
                                $discount_percentage = round(100 - ($product['sale_price'] / $product['price'] * 100));
                                ?>
                                <div class="absolute top-3 left-3 bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                    Promocja -<?php echo $discount_percentage; ?>%
                                </div>
                            <?php endif; ?>
                            
                            <img src="<?php echo $product['image_path'] ?? 'assets/images/product-placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-48 object-cover">
                        </a>
                        
                        <div class="p-4">
                            <div class="text-xs text-gray-500 mb-1">
                                <?php echo htmlspecialchars($product['brand_name'] ?? ''); ?> | 
                                <?php echo htmlspecialchars($product['category_name'] ?? ''); ?>
                            </div>
                            
                            <a href="product.php?slug=<?php echo $product['slug']; ?>" class="block mb-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition line-clamp-2">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                            </a>
                            
                            <div class="flex items-center mb-3">
                                <?php
                                // Pobierz średnią ocenę dla produktu
                                $rating_query = "SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = ? AND status = 'approved'";
                                $rating_stmt = $conn->prepare($rating_query);
                                $rating_stmt->bind_param('i', $product['id']);
                                $rating_stmt->execute();
                                $rating_result = $rating_stmt->get_result();
                                $rating = 0;
                                
                                if ($rating_result && $rating_result->num_rows > 0) {
                                    $rating_row = $rating_result->fetch_assoc();
                                    $rating = round($rating_row['avg_rating'], 1);
                                }
                                
                                // Wyświetl gwiazdki
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="ri-star-fill text-yellow-400"></i>';
                                    } else if ($i - 0.5 <= $rating) {
                                        echo '<i class="ri-star-half-fill text-yellow-400"></i>';
                                    } else {
                                        echo '<i class="ri-star-line text-yellow-400"></i>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <?php if (!empty($product['sale_price'])): ?>
                                        <span class="text-primary font-bold">
                                            <?php echo number_format($product['sale_price'], 2, ',', ' '); ?> zł
                                        </span>
                                        <span class="text-gray-400 line-through text-sm ml-2">
                                            <?php echo number_format($product['price'], 2, ',', ' '); ?> zł
                                        </span>
                                    <?php else: ?>
                                        <span class="text-primary font-bold">
                                            <?php echo number_format($product['price'], 2, ',', ' '); ?> zł
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                        class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition">
                                    <i class="ri-shopping-cart-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Skrypt JS do obsługi dodawania do koszyka
$extra_js = '<script>
function showNotification(message, type = "success") {
    // Usuń istniejące powiadomienie jeśli istnieje
    const existingNotification = document.querySelector(".notification");
    if (existingNotification) {
        existingNotification.remove();
    }

    // Stwórz nowe powiadomienie
    const notification = document.createElement("div");
    notification.className = `notification fixed top-20 right-4 z-40 flex items-center p-4 mb-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    
    // Dodaj odpowiedni kolor w zależności od typu
    if (type === "success") {
        notification.classList.add("bg-green-100", "text-green-800", "border", "border-green-200");
    } else {
        notification.classList.add("bg-red-100", "text-red-800", "border", "border-red-200");
    }

    // Dodaj ikonę
    const icon = document.createElement("i");
    icon.className = type === "success" ? "ri-checkbox-circle-fill mr-2 text-xl" : "ri-error-warning-fill mr-2 text-xl";
    notification.appendChild(icon);

    // Dodaj tekst
    const text = document.createElement("span");
    text.className = "text-sm font-medium";
    text.textContent = message;
    notification.appendChild(text);

    // Dodaj przycisk zamknięcia
    const closeButton = document.createElement("button");
    closeButton.className = "ml-4 text-gray-500 hover:text-gray-700 focus:outline-none";
    closeButton.innerHTML = "<i class=\"ri-close-line text-xl\"></i>";
    closeButton.onclick = () => notification.remove();
    notification.appendChild(closeButton);

    // Dodaj do body
    document.body.appendChild(notification);

    // Animacja wejścia
    setTimeout(() => {
        notification.classList.remove("translate-x-full");
    }, 100);

    // Automatyczne zamknięcie po 3 sekundach
    setTimeout(() => {
        notification.classList.add("translate-x-full");
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function addToCart(productId) {
    fetch("cart-actions.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "action=add&product_id=" + productId + "&quantity=1"
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualizacja liczby produktów w koszyku
            const cartCount = document.getElementById("cartCount");
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove("hidden");
            }
            
            showNotification("Produkt został dodany do koszyka", "success");
        } else {
            showNotification("Wystąpił błąd: " + data.message, "error");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        showNotification("Wystąpił błąd podczas dodawania produktu do koszyka", "error");
    });
}
</script>';

include 'includes/footer.php';
?> 