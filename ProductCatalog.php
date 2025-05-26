<?php
// Strona katalogu produktów
$page_title = "Katalog produktów";
require_once 'includes/config.php';

// Obsługa filtrów
$category = isset($_GET['category']) ? (array)$_GET['category'] : [];
$brand = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
// Upewnij się, że $brand jest zawsze tablicą
if (!is_array($brand)) {
    $brand = [];
}
$price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'price_asc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Pobieranie marek dla filtrów
$brands_query = "SELECT * FROM brands ORDER BY name";
$brands = [];
$brands_result = $conn->query($brands_query);

if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Pobieranie kategorii dla filtrów
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories = [];
$categories_result = $conn->query($categories_query);

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Budowanie zapytania SQL
$query = "SELECT p.*, pi.image_path, b.name as brand_name, c.name as category_name 
          FROM products p 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
          INNER JOIN brands b ON p.brand_id = b.id
          INNER JOIN categories c ON p.category_id = c.id
          WHERE p.status = 'published'";

// Dodanie filtrów do zapytania
if (!empty($category)) {
    $category_ids = [];
    foreach ($categories as $cat_row) {
        if (in_array($cat_row['slug'], $category)) {
            $category_ids[] = (int)$cat_row['id'];
        }
    }
    if (!empty($category_ids)) {
        $query .= " AND p.category_id IN (" . implode(',', $category_ids) . ")";
    }
}

if (!empty($brand)) {
    $brand_ids = [];
    foreach ($brands as $b) {
        if (in_array($b['slug'], $brand)) {
            $brand_ids[] = (int)$b['id'];
        }
    }
    if (!empty($brand_ids)) {
        $query .= " AND p.brand_id IN (" . implode(',', $brand_ids) . ")";
    }
}

if ($price_min > 0) {
    $query .= " AND p.price >= $price_min";
}

if ($price_max > 0) {
    $query .= " AND p.price <= $price_max";
}

// Sortowanie
switch ($sort) {
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    case 'popularity':
        $query .= " ORDER BY p.popularity DESC";
        break;
    default:
        $query .= " ORDER BY p.price ASC";
}

// Pobieranie liczby wszystkich pasujących produktów (bez limitu)
$count_query = preg_replace('/SELECT p\.\*, pi\.image_path, b\.name as brand_name, c\.name as category_name/', 'SELECT COUNT(*) as total', $query);
$count_query = preg_replace('/ORDER BY.*$/', '', $count_query);
$count_result = $conn->query($count_query);
$total_products = 0;

if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_products = $row['total'];
}

$total_pages = ceil($total_products / $per_page);

// Dodanie limitu do zapytania głównego
$query .= " LIMIT $offset, $per_page";

// Pobieranie produktów
$products = [];
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Pobieranie statystyk cenowych dla filtrów
$stats_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'published'";
$stats_result = $conn->query($stats_query);
$price_stats = null;

if ($stats_result && $stats_result->num_rows > 0) {
    $price_stats = $stats_result->fetch_assoc();
} else {
    // Domyślne wartości, jeśli baza jest pusta
    $price_stats = [
        'min_price' => 0,
        'max_price' => 5000
    ];
}

include 'includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="bg-white border-b">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center text-sm text-gray-500">
            <a href="index.php" class="hover:text-primary">Strona główna</a>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <span class="text-gray-700 font-medium">Katalog produktów</span>
            <?php if (!empty($category)): ?>
            <i class="ri-arrow-right-s-line mx-2"></i>
            <?php
            $cat_name = '';
            foreach ($categories as $cat) {
                if ($cat['slug'] == $category[0]) {
                    $cat_name = $cat['name'];
                    break;
                }
            }
            ?>
            <span class="text-gray-700 font-medium"><?php echo $cat_name; ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Powrót do strony głównej -->
<div class="container mx-auto px-4 py-4">
    <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-primary transition">
        <i class="ri-arrow-left-line mr-2"></i>
        <span>Powrót do strony głównej</span>
    </a>
</div>

<!-- Tytuł strony -->
<div class="container mx-auto px-4 py-4">
    <h1 class="text-3xl font-bold text-gray-900">Katalog produktów</h1>
    <p class="text-gray-600 mt-2">Przeglądaj naszą pełną ofertę produktów motocyklowych</p>
</div>

<!-- Główna zawartość -->
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filtry boczne -->
        <div class="lg:w-1/4 xl:w-1/5">
            <!-- Przycisk filtrów dla mobile -->
            <div class="lg:hidden mb-4">
                <button id="filter-toggle" class="w-full bg-white border border-gray-200 rounded py-3 px-4 flex justify-between items-center !rounded-button">
                    <span class="font-medium">Filtry</span>
                    <i class="ri-filter-3-line"></i>
                </button>
            </div>

            <!-- Panel filtrów -->
            <div id="filters-panel" class="hidden lg:block bg-white rounded-lg shadow-sm p-6 mb-6">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" id="filters-form">
                    <!-- Ukryte pole sortowania -->
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <!-- Kategorie -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-lg mb-4">Kategorie</h3>
                        <ul class="space-y-3">
                            <?php foreach ($categories as $cat): ?>
                            <li>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="category[]" value="<?php echo $cat['slug']; ?>" <?php echo in_array($cat['slug'], $category) ? 'checked' : ''; ?> onchange="this.form.submit();">
                                    <span class="checkmark"></span>
                                    <?php echo $cat['name']; ?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Marki -->
                    <div class="mb-6 border-t pt-6">
                        <h3 class="font-semibold text-lg mb-4">Marki</h3>
                        <ul class="space-y-3">
                            <?php foreach ($brands as $b): ?>
                            <li>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="brand[]" value="<?php echo $b['slug']; ?>" <?php echo in_array($b['slug'], $brand) ? 'checked' : ''; ?> onchange="this.form.submit();">
                                    <span class="checkmark"></span>
                                    <?php echo $b['name']; ?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Cena -->
                    <div class="mb-6 border-t pt-6">
                        <h3 class="font-semibold text-lg mb-4">Cena (zł)</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="text-sm text-gray-600">Od</label>
                                <input type="number" name="price_min" value="<?php echo $price_min ?: ''; ?>" placeholder="<?php echo number_format($price_stats['min_price']); ?>" class="w-full p-2 border rounded">
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Do</label>
                                <input type="number" name="price_max" value="<?php echo $price_max ?: ''; ?>" placeholder="<?php echo number_format($price_stats['max_price']); ?>" class="w-full p-2 border rounded">
                            </div>
                        </div>
                    </div>

                    <!-- Przyciski formularza -->
                    <div class="flex space-x-4 pt-4 border-t">
                        <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-button font-medium hover:bg-opacity-90 transition">
                            Filtruj
                        </button>
                        <button type="button" id="reset-filters" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-button font-medium hover:bg-gray-300 transition">
                            Resetuj
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Siatka produktów -->
        <div class="lg:w-3/4 xl:w-4/5">
            <!-- Pasek sortowania i licznik -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <p class="text-gray-600">Pokazano <span class="font-semibold"><?php echo count($products); ?></span> z <span class="font-semibold"><?php echo $total_products; ?></span> produktów</p>
                </div>

                <div class="flex items-center">
                    <span class="text-gray-600 mr-2">Sortuj według:</span>
                    <select id="sort-select" class="border rounded p-2">
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Cena: od najniższej</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Cena: od najwyższej</option>
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Od najnowszych</option>
                        <option value="popularity" <?php echo $sort == 'popularity' ? 'selected' : ''; ?>>Popularity</option>
                    </select>
                </div>
            </div>
            
            <!-- Produkty -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $image = $product['image_path'] ?? 'assets/images/placeholder.jpg';
                        
                        // Oblicz procent rabatu jeśli jest
                        $discount_percentage = '';
                        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
                            $discount_percentage = round(100 - ($product['sale_price'] / $product['price'] * 100));
                        }
                        ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                            <a href="product.php?slug=<?php echo $product['slug']; ?>" class="block relative">
                                <!-- Znacznik promocji -->
                                <?php if (!empty($discount_percentage)): ?>
                                <div class="absolute top-3 left-3 bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                    Promocja -<?php echo $discount_percentage; ?>%
                                </div>
                                <?php endif; ?>
                                
                                <img src="<?php echo $image; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-48 object-cover">
                            </a>
                            
                            <div class="p-4">
                                <div class="text-xs text-gray-500 mb-1"><?php echo $product['brand_name'] ?? ''; ?> | <?php echo $product['category_name'] ?? ''; ?></div>
                                <a href="product.php?slug=<?php echo $product['slug']; ?>" class="block mb-2">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-primary transition line-clamp-2"><?php echo $product['name']; ?></h3>
                                </a>
                                
                                <div class="flex justify-between items-center">
                                    <div>
                                        <?php if (!empty($product['sale_price'])): ?>
                                        <span class="text-primary font-bold"><?php echo number_format($product['sale_price'], 2, ',', ' '); ?> zł</span>
                                        <span class="text-gray-400 line-through text-sm ml-2"><?php echo number_format($product['price'], 2, ',', ' '); ?> zł</span>
                                        <?php else: ?>
                                        <span class="text-primary font-bold"><?php echo number_format($product['price'], 2, ',', ' '); ?> zł</span>
                                        <?php endif; ?>
                                    </div>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition">
                                        <i class="ri-shopping-cart-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-12 text-center">
                        <div class="text-gray-500 mb-4"><i class="ri-shopping-basket-line text-5xl"></i></div>
                        <h3 class="text-xl font-semibold mb-2">Brak produktów</h3>
                        <p class="text-gray-500">Nie znaleziono produktów spełniających kryteria wyszukiwania.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Paginacja -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-4 py-2 border rounded hover:bg-gray-100 transition">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    if ($start_page > 1): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-4 py-2 border rounded hover:bg-gray-100 transition">
                        1
                    </a>
                    <?php if ($start_page > 2): ?>
                    <span class="px-4 py-2">...</span>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-4 py-2 border rounded <?php echo $i == $page ? 'bg-primary text-white' : 'hover:bg-gray-100'; ?> transition">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-4 py-2">...</span>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="px-4 py-2 border rounded hover:bg-gray-100 transition">
                        <?php echo $total_pages; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-4 py-2 border rounded hover:bg-gray-100 transition">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Newsletter -->
<section class="py-16 bg-gray-900 text-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Zapisz się do newslettera</h2>
            <p class="text-gray-300 mb-8">Otrzymuj powiadomienia o promocjach, nowych produktach i poradach dotyczących Twojego motocykla.</p>
            
            <form action="newsletter-signup.php" method="POST" class="flex flex-col sm:flex-row gap-4">
                <input type="email" name="email" placeholder="Twój adres email" required
                    class="flex-grow py-3 px-4 rounded-button bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:border-primary">
                <button type="submit" class="bg-primary text-white py-3 px-8 rounded-button font-medium hover:bg-opacity-90 transition">
                    Zapisz się
                </button>
            </form>
        </div>
    </div>
</section>

<?php
// Skrypt JS do obsługi dodawania do koszyka i inne funkcje
$extra_js = <<<EOT
<script>
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
                if (data.cart_count > 0) {
                    cartCount.classList.remove('hidden');
                }
            }
            
            // Pokaż komunikat o sukcesie
            const successMessage = document.createElement('div');
            successMessage.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-y-0';
            successMessage.innerHTML = `
                <div class="flex items-center">
                    <i class="ri-checkbox-circle-fill mr-2"></i>
                    <span>Produkt został dodany do koszyka</span>
                </div>
            `;
            document.body.appendChild(successMessage);
            
            // Animacja wejścia
            requestAnimationFrame(() => {
                successMessage.style.transform = 'translateY(0)';
            });
            
            // Usuń komunikat po 3 sekundach z animacją wyjścia
            setTimeout(() => {
                successMessage.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 3000);
        } else {
            alert('Wystąpił błąd: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas dodawania produktu do koszyka');
    });
}

// Obsługa filtrów na urządzeniach mobilnych
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.getElementById('filter-toggle');
    const filtersPanel = document.getElementById('filters-panel');
    
    if (filterToggle && filtersPanel) {
        filterToggle.addEventListener('click', function() {
            filtersPanel.classList.toggle('hidden');
        });
    }
    
    // Obsługa sortowania
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            // Dodaj wartość sortowania do formularza filtrów i wyślij go
            const form = document.getElementById('filters-form');
            
            // Stwórz ukryte pole dla sortowania jeśli nie istnieje
            let sortInput = form.querySelector('input[name="sort"]');
            if (!sortInput) {
                sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                form.appendChild(sortInput);
            }
            
            // Ustaw wartość sortowania i wyślij formularz
            sortInput.value = this.value;
            form.submit();
        });
    }
    
    // Obsługa przycisku resetowania filtrów
    const resetButton = document.getElementById('reset-filters');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'ProductCatalog.php';
        });
    }
});
</script>
EOT;

include 'includes/footer.php';
?>
