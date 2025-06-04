<?php
// Włączenie pełnego raportowania błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Stała określająca, że jesteśmy w panelu administracyjnym
define('ADMIN_PANEL', true);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

$page_title = "Zarządzanie produktami";

// Parametry filtrowania
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brand_filter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$stock_filter = isset($_GET['stock']) ? $conn->real_escape_string($_GET['stock']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Parametry sortowania
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'id';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'desc';

// Obsługa usuwania produktu
$message = '';
$message_type = '';

if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Usuwanie zdjęć produktu
    $images_query = "DELETE FROM product_images WHERE product_id = $product_id";
    $conn->query($images_query);
    
    // Usuwanie produktu
    $delete_query = "DELETE FROM products WHERE id = $product_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $message = 'Produkt został usunięty.';
        $message_type = 'success';
    } else {
        $message = 'Wystąpił błąd podczas usuwania produktu.';
        $message_type = 'error';
    }
}

// Aktualizacja statusu produktu
if (isset($_POST['update_status']) && isset($_POST['product_id']) && isset($_POST['status'])) {
    $product_id = (int)$_POST['product_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE products SET status = '$status' WHERE id = $product_id";
    
    if ($conn->query($update_query) === TRUE) {
        $message = 'Status produktu został zaktualizowany.';
        $message_type = 'success';
    } else {
        $message = 'Wystąpił błąd podczas aktualizacji statusu produktu.';
        $message_type = 'error';
    }
}

// Tworzenie klauzuli WHERE dla filtrowania
$where_clause = "WHERE 1=1";

if ($category_filter > 0) {
    $where_clause .= " AND category_id = $category_filter";
}

if ($brand_filter > 0) {
    $where_clause .= " AND brand_id = $brand_filter";
}

if ($status_filter !== '') {
    $where_clause .= " AND status = '$status_filter'";
}

if ($stock_filter === 'in_stock') {
    $where_clause .= " AND stock > 0";
} elseif ($stock_filter === 'out_of_stock') {
    $where_clause .= " AND stock = 0";
} elseif ($stock_filter === 'low_stock') {
    $where_clause .= " AND stock > 0 AND stock <= 5";
}

if ($search !== '') {
    $where_clause .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Tworzenie klauzuli ORDER BY dla sortowania
$order_clause = "ORDER BY $sort $order";

// Kalkulacja offsetu dla paginacji
$offset = ($page - 1) * $per_page;

// Tworzenie zapytania - używamy bardziej solidnego zapytania, które będzie działać nawet jeśli nie ma zdjęć lub kolumny position
$query = "SELECT p.*, c.name as category_name, b.name as brand_name,
          pi.image_path as thumbnail
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
          $where_clause
          $order_clause
          LIMIT $offset, $per_page";

// Wykonanie zapytania
$result = $conn->query($query);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Upewniamy się, że ścieżka do zdjęcia jest poprawna
        if (!empty($row['thumbnail'])) {
            $thumb = $row['thumbnail'];
            // Zamień backslash na slash
            $thumb = str_replace('\\', '/', $thumb);
            // Zamień 'uploads/products/' na 'uploads/produkty/' jeśli katalog na serwerze jest po polsku
            $thumb = str_replace('uploads/products/', 'uploads/produkty/', $thumb);
            if (strpos($thumb, 'uploads/') === 0) {
                $row['thumbnail'] = '/' . ltrim($thumb, '/');
            } else if (strpos($thumb, '/') === false) {
                $row['thumbnail'] = '/uploads/produkty/' . $thumb;
            } else {
                $row['thumbnail'] = '/' . ltrim($thumb, '/');
            }
        }
        $products[] = $row;
    }
}

// Pobieranie kategorii do filtru
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[$category['id']] = $category['name'];
    }
}

// Pobieranie marek do filtru
$brands_query = "SELECT * FROM brands ORDER BY name";
$brands_result = $conn->query($brands_query);
$brands = [];

if ($brands_result && $brands_result->num_rows > 0) {
    while ($brand = $brands_result->fetch_assoc()) {
        $brands[$brand['id']] = $brand['name'];
    }
}

// Liczba wszystkich produktów (dla paginacji)
$count_query = "SELECT COUNT(*) as total FROM products $where_clause";
$count_result = $conn->query($count_query);
$total_products = 0;
if ($count_result && $count_result->num_rows > 0) {
    $total_products = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_products / $per_page);

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Zarządzanie produktami</h1>
            <p class="text-gray-600">Przeglądaj, dodawaj i edytuj produkty w sklepie</p>
        </div>
        
        <div class="mt-4 md:mt-0">
            <a href="product_form.php" class="inline-flex items-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                <i class="ri-add-line mr-2"></i> Dodaj nowy produkt
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <!-- Filtry i wyszukiwanie -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Wyszukiwarka -->
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Szukaj produktów..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 pl-10">
                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="ri-search-line"></i>
                    </div>
                </div>
            </div>
            
            <!-- Filtr kategorii -->
            <div>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">Wszystkie kategorie</option>
                    <?php foreach ($categories as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $category_filter == $id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtr marki -->
            <div>
                <select name="brand" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">Wszystkie marki</option>
                    <?php foreach ($brands as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $brand_filter == $id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Przyciski akcji -->
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                    Filtruj
                </button>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="bg-gray-100 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-200">
                    Resetuj
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lista produktów -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <?php if (empty($products)): ?>
        <div class="p-8 text-center">
            <i class="ri-inbox-line text-5xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-800 mb-1">Brak produktów</h2>
            <p class="text-gray-600">Nie znaleziono produktów spełniających kryteria wyszukiwania.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Produkt
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=price&order=<?php echo $sort === 'price' && $order === 'asc' ? 'desc' : 'asc'; ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&search=<?php echo $search; ?>" class="flex items-center">
                                Cena
                                <?php if ($sort === 'price'): ?>
                                <i class="ri-<?php echo $order === 'asc' ? 'arrow-up' : 'arrow-down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?sort=stock&order=<?php echo $sort === 'stock' && $order === 'asc' ? 'desc' : 'asc'; ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&search=<?php echo $search; ?>" class="flex items-center">
                                Stan magazynowy
                                <?php if ($sort === 'stock'): ?>
                                <i class="ri-<?php echo $order === 'asc' ? 'arrow-up' : 'arrow-down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kategoria / Marka
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <?php if (!empty($product['thumbnail'])): ?>
                                    <img class="h-10 w-10 object-cover rounded-md" 
                                         src="../<?php echo htmlspecialchars($product['thumbnail']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.onerror=null; this.src='/assets/images/no-image.png';">
                                    <span style="font-size:10px; color:#888; word-break:break-all; display:block; max-width:80px;">
                                        <?php echo htmlspecialchars($product['thumbnail']); ?>
                                    </span>
                                    <?php else: ?>
                                    <div class="h-10 w-10 rounded-md bg-gray-200 flex items-center justify-center text-gray-400">
                                        <i class="ri-image-line"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo $product['id']; ?> | 
                                        <?php if (!empty($product['slug'])): ?>
                                        <a href="/Sklep_mech_motocykl_Rodzinka_Rogoz/product.php?slug=<?php echo urlencode($product['slug']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                            Podgląd 
                                            <i class="ri-external-link-line text-xs"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-400 cursor-not-allowed">Brak podglądu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">
                                <?php echo number_format($product['price'], 2, ',', ' '); ?> zł
                            </div>
                            <?php if (!empty($product['sale_price'])): ?>
                            <div class="text-sm text-red-600">
                                <?php echo number_format($product['sale_price'], 2, ',', ' '); ?> zł
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($product['stock'] > 10): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <?php echo $product['stock']; ?> szt.
                            </span>
                            <?php elseif ($product['stock'] > 0): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <?php echo $product['stock']; ?> szt.
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Brak na stanie
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Brak kategorii'); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($product['brand_name'] ?? 'Brak marki'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($product['status']) {
                                case 'published':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Aktywny';
                                    break;
                                case 'out_of_stock':
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = 'Niedostępny';
                                    break;
                                case 'draft':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Szkic';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = 'Nieznany';
                            }
                            ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- Przycisk zmiany statusu -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" type="button" class="text-gray-500 hover:text-gray-700">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" style="display: none;">
                                        <div class="py-1" role="menu" aria-orientation="vertical">
                                            <form method="POST" class="block w-full text-left">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                
                                                <button type="submit" name="status" value="published" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" role="menuitem">
                                                    Ustaw jako aktywny
                                                </button>
                                                <button type="submit" name="status" value="out_of_stock" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" role="menuitem">
                                                    Ustaw jako niedostępny
                                                </button>
                                                <button type="submit" name="status" value="draft" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" role="menuitem">
                                                    Ustaw jako szkic
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Przycisk edycji -->
                                <a href="product_form.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                
                                <!-- Przycisk usuwania -->
                                <form method="POST" class="inline" onsubmit="return confirm('Czy na pewno chcesz usunąć ten produkt?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="text-red-600 hover:text-red-800">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginacja -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-between items-center bg-white rounded-lg shadow-sm p-4">
        <div class="text-sm text-gray-600">
            Wyświetlanie <?php echo count($products); ?> z <?php echo $total_products; ?> produktów
        </div>
        <div class="flex space-x-1">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&search=<?php echo $search; ?>" class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <?php endif; ?>
            
            <?php
            // Określenie zakresu stron do wyświetlenia
            $range = 2;
            $start_page = max(1, $page - $range);
            $end_page = min($total_pages, $page + $range);
            
            // Link do pierwszej strony, jeśli nie jest widoczna w zakresie
            if ($start_page > 1) {
                echo '<a href="?page=1&sort=' . $sort . '&order=' . $order . '&category=' . $category_filter . '&brand=' . $brand_filter . '&status=' . $status_filter . '&stock=' . $stock_filter . '&search=' . $search . '" ';
                echo 'class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">1</a>';
                
                if ($start_page > 2) {
                    echo '<span class="px-3 py-1 text-gray-500">...</span>';
                }
            }
            
            // Wyświetlenie numerów stron w określonym zakresie
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = $i === $page ? 'bg-blue-50 text-blue-600 border-blue-300' : 'text-gray-700 hover:bg-gray-50';
                
                echo '<a href="?page=' . $i . '&sort=' . $sort . '&order=' . $order . '&category=' . $category_filter . '&brand=' . $brand_filter . '&status=' . $status_filter . '&stock=' . $stock_filter . '&search=' . $search . '" ';
                echo 'class="px-3 py-1 rounded-md border ' . $active_class . '">' . $i . '</a>';
            }
            
            // Link do ostatniej strony, jeśli nie jest widoczna w zakresie
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="px-3 py-1 text-gray-500">...</span>';
                }
                
                echo '<a href="?page=' . $total_pages . '&sort=' . $sort . '&order=' . $order . '&category=' . $category_filter . '&brand=' . $brand_filter . '&status=' . $status_filter . '&stock=' . $stock_filter . '&search=' . $search . '" ';
                echo 'class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&category=<?php echo $category_filter; ?>&brand=<?php echo $brand_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&search=<?php echo $search; ?>" class="px-3 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="ri-arrow-right-s-line"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Dodanie Alpine.js dla rozwijanego menu -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>

<?php
include 'includes/footer.php';
?>
