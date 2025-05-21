<?php
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
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$stock_filter = isset($_GET['stock']) ? sanitize($_GET['stock']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Parametry sortowania
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'id';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'desc';

// Obsługa usuwania produktu
$message = '';
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Usuwanie zdjęć produktu
    $images_query = "DELETE FROM product_images WHERE product_id = $product_id";
    $conn->query($images_query);
    
    // Usuwanie produktu
    $delete_query = "DELETE FROM products WHERE id = $product_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $message = 'Produkt został usunięty.';
    } else {
        $message = 'Wystąpił błąd podczas usuwania produktu.';
    }
}

// Aktualizacja statusu produktu
if (isset($_POST['update_status']) && isset($_POST['product_id']) && isset($_POST['status'])) {
    $product_id = (int)$_POST['product_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE products SET status = '$status' WHERE id = $product_id";
    
    if ($conn->query($update_query) === TRUE) {
        $message = 'Status produktu został zaktualizowany.';
    } else {
        $message = 'Wystąpił błąd podczas aktualizacji statusu produktu.';
    }
}

// Pobieranie kategorii i marek dla filtrów
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}

$brands_query = "SELECT * FROM brands ORDER BY name";
$brands_result = $conn->query($brands_query);
$brands = [];

if ($brands_result && $brands_result->num_rows > 0) {
    while ($brand = $brands_result->fetch_assoc()) {
        $brands[] = $brand;
    }
}

// Budowanie zapytania SQL
$query = "SELECT p.*, c.name as category_name, b.name as brand_name, 
         (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as order_count,
         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as image_path
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         LEFT JOIN brands b ON p.brand_id = b.id 
         WHERE 1=1";

// Dodawanie filtrów
if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}

if ($brand_filter > 0) {
    $query .= " AND p.brand_id = $brand_filter";
}

if (!empty($status_filter)) {
    $query .= " AND p.status = '$status_filter'";
}

if ($stock_filter === 'low') {
    $query .= " AND p.stock <= 5 AND p.stock > 0";
} elseif ($stock_filter === 'out') {
    $query .= " AND p.stock = 0";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.sku LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Dodawanie sortowania
$valid_sort_fields = ['id' => 'p.id', 'name' => 'p.name', 'price' => 'p.price', 'stock' => 'p.stock', 'sales' => 'order_count'];
$valid_order_values = ['asc', 'desc'];

$sort_field = isset($valid_sort_fields[$sort]) ? $valid_sort_fields[$sort] : $valid_sort_fields['id'];
$order_value = in_array(strtolower($order), $valid_order_values) ? strtolower($order) : 'desc';

$query .= " ORDER BY $sort_field $order_value";

// Obliczanie całkowitej liczby wyników dla paginacji
$count_query = str_replace("SELECT p.*, c.name as category_name, b.name as brand_name, 
         (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as order_count,
         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as image_path", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/ORDER BY.*$/i', '', $count_query);

$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Ograniczenie wyników do bieżącej strony
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Wykonanie zapytania
$result = $conn->query($query);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

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
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>
