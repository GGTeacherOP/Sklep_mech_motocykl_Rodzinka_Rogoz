<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sprawdź czy plik config.php istnieje
$config_path = __DIR__ . '/../includes/config.php';
if (!file_exists($config_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Błąd konfiguracji',
        'debug' => 'Nie znaleziono pliku config.php w ścieżce: ' . $config_path
    ]);
    exit;
}

require_once $config_path;

try {
    // Pobierz zapytanie wyszukiwania
    $search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if (empty($search_query)) {
        throw new Exception('Empty search query');
    }

    // Sprawdź połączenie z bazą danych
    if (!$conn) {
        throw new Exception('Brak połączenia z bazą danych');
    }

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
    $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, pi.image_path, b.name as brand_name, c.name as category_name 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            $where_clause
            ORDER BY p.name ASC
            LIMIT 5";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    // Przygotuj HTML dla wyników
    $html = '';
    if (!empty($products)) {
        foreach ($products as $product) {
            $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
            $html .= '<a href="product.php?slug=' . htmlspecialchars($product['slug']) . '" class="flex items-center p-2 hover:bg-gray-50 transition-colors duration-200">';
            $html .= '<img src="' . (htmlspecialchars($product['image_path'] ?? 'assets/images/product-placeholder.jpg')) . '" alt="' . htmlspecialchars($product['name']) . '" class="w-12 h-12 object-cover rounded">';
            $html .= '<div class="ml-3 flex-grow">';
            $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($product['name']) . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . htmlspecialchars($product['brand_name'] ?? '') . ' | ' . htmlspecialchars($product['category_name'] ?? '') . '</div>';
            $html .= '</div>';
            $html .= '<div class="text-sm font-medium text-primary">' . number_format($price, 2, ',', ' ') . ' zł</div>';
            $html .= '</a>';
        }
        $html .= '<div class="border-t border-gray-200 p-2 text-center">';
        $html .= '<a href="search.php?query=' . urlencode($search_query) . '" class="text-sm text-primary hover:underline">Zobacz wszystkie wyniki</a>';
        $html .= '</div>';
    } else {
        $html = '<div class="p-4 text-center text-gray-500">Brak wyników</div>';
    }

    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    error_log('Search preview error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Wystąpił błąd podczas wyszukiwania',
        'debug' => $e->getMessage()
    ]);
}
?> 