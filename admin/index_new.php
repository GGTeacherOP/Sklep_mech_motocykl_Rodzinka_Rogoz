<?php
// Włączenie wyświetlania błędów PHP
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

$page_title = "Panel Administratora";

// Proste zapytania bez skomplikowanej logiki
// Produkty
$products_count = 0;
$products_result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($products_result && $products_result->num_rows > 0) {
    $products_count = $products_result->fetch_assoc()['count'];
}

// Użytkownicy
$users_count = 0;
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($users_result && $users_result->num_rows > 0) {
    $users_count = $users_result->fetch_assoc()['count'];
}

// Zamówienia
$orders_count = 0;
$orders_revenue = 0;
$orders_result = $conn->query("SELECT COUNT(*) as count, SUM(total) as revenue FROM orders");
if ($orders_result && $orders_result->num_rows > 0) {
    $orders_data = $orders_result->fetch_assoc();
    $orders_count = $orders_data['count'];
    $orders_revenue = $orders_data['revenue'] ?? 0;
}

// Ostatnie zamówienia
$recent_orders = [];
$recent_orders_result = $conn->query("
    SELECT o.*, u.email, u.first_name, u.last_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC LIMIT 5
");

if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
    while ($row = $recent_orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Najlepiej sprzedające się produkty
$best_selling = [];
$best_selling_result = $conn->query("
    SELECT p.name, p.slug, COUNT(oi.product_id) as sold_count 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY oi.product_id 
    ORDER BY sold_count DESC 
    LIMIT 5
");

if ($best_selling_result && $best_selling_result->num_rows > 0) {
    while ($row = $best_selling_result->fetch_assoc()) {
        $best_selling[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Panel Administratora</h1>
        <p class="text-gray-600">Witaj <?php echo $_SESSION['admin_name'] ?? 'Administrator'; ?></p>
    </div>
    
    <!-- Karty statystyk -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Przychód -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-4">
                    <i class="ri-money-euro-circle-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Przychód</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($orders_revenue, 2, ',', ' '); ?> zł</h3>
                </div>
            </div>
        </div>
        
        <!-- Zamówienia -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4">
                    <i class="ri-shopping-cart-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Zamówienia</p>
                    <h3 class="text-2xl font-bold"><?php echo $orders_count; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Produkty -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-4">
                    <i class="ri-shopping-bag-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Produkty</p>
                    <h3 class="text-2xl font-bold"><?php echo $products_count; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Użytkownicy -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-4">
                    <i class="ri-user-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Użytkownicy</p>
                    <h3 class="text-2xl font-bold"><?php echo $users_count; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Ostatnie zamówienia -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Ostatnie zamówienia</h2>
            
            <?php if (empty($recent_orders)): ?>
            <p class="text-gray-500">Brak zamówień.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Klient</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Kwota</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2">#<?php echo $order['id']; ?></td>
                            <td class="py-2">
                                <?php 
                                if (!empty($order['first_name']) && !empty($order['last_name'])) {
                                    echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                } else {
                                    echo htmlspecialchars($order['email'] ?? 'Brak danych');
                                }
                                ?>
                            </td>
                            <td class="py-2">
                                <?php
                                $status = $order['status'] ?? 'pending';
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($status) {
                                    case 'pending':
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        $status_text = 'Oczekujące';
                                        break;
                                    case 'processing':
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        $status_text = 'W realizacji';
                                        break;
                                    case 'shipped':
                                        $status_class = 'bg-purple-100 text-purple-800';
                                        $status_text = 'Wysłane';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Zakończone';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Anulowane';
                                        break;
                                    default:
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        $status_text = 'Nieznany';
                                }
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="py-2"><?php echo number_format($order['total'] ?? 0, 2, ',', ' '); ?> zł</td>
                            <td class="py-2">
                                <a href="orders.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:underline">
                                    Szczegóły
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Najlepiej sprzedające się produkty -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Najlepiej sprzedające się produkty</h2>
            
            <?php if (empty($best_selling)): ?>
            <p class="text-gray-500">Brak danych o sprzedaży produktów.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($best_selling as $index => $product): ?>
                <div class="flex items-center">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm">
                        <?php echo $index + 1; ?>
                    </span>
                    <div class="flex-grow">
                        <p class="font-medium">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo min(100, ($product['sold_count'] / $best_selling[0]['sold_count']) * 100); ?>%"></div>
                        </div>
                    </div>
                    <span class="font-medium ml-4"><?php echo $product['sold_count']; ?> szt.</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Szybkie linki -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="products.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-4">
                    <i class="ri-add-line text-xl"></i>
                </div>
                <h3 class="font-semibold">Dodaj produkt</h3>
            </div>
        </a>
        
        <a href="orders.php?status=pending" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mb-4">
                    <i class="ri-time-line text-xl"></i>
                </div>
                <h3 class="font-semibold">Oczekujące zamówienia</h3>
            </div>
        </a>
        
        <a href="messages.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-4">
                    <i class="ri-message-2-line text-xl"></i>
                </div>
                <h3 class="font-semibold">Wiadomości</h3>
            </div>
        </a>
        
        <a href="analytics.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-4">
                    <i class="ri-bar-chart-line text-xl"></i>
                </div>
                <h3 class="font-semibold">Analityka</h3>
            </div>
        </a>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
